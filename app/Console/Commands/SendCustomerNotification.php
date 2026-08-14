<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Customer, CustomerDeviceToken, RentalBooking, NotificationLog};
use App\Jobs\SendNotificationJob;

class SendCustomerNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:customer
                            {customer : Customer ID, email or mobile number}
                            {--booking= : Booking ID (required for booking event types)}
                            {--event=new_booking : new_booking|extension|completion|penalty|doc_upload_reminder}
                            {--title= : Custom push title (sends directly, ignores --event templates)}
                            {--body= : Custom push body (used with --title)}
                            {--now : Send immediately instead of queueing (no worker needed)}
                            {--dry-run : Show what would be sent without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a notification (push/email) to an existing customer from the command line';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customer = $this->resolveCustomer($this->argument('customer'));
        if (!$customer) {
            $this->error('Customer not found: ' . $this->argument('customer'));
            return self::FAILURE;
        }

        $this->info('Customer  : #' . $customer->customer_id . ' ' . trim($customer->firstname . ' ' . $customer->lastname));
        $this->line('Email     : ' . ($customer->email ?: '(none)'));
        $this->line('Mobile    : ' . ($customer->mobile_number ?: '(none)'));

        // Same lookup the job uses, so the CLI reflects real deliverability.
        $deviceToken = CustomerDeviceToken::where([
            'customer_id' => $customer->customer_id,
            'is_deleted'  => 0,
            'is_error'    => 0,
        ])->value('device_token');

        $this->line('Device    : ' . ($deviceToken ? substr($deviceToken, 0, 24) . '...' : '(no usable token - push will be skipped)'));

        $customTitle = $this->option('title');

        return $customTitle
            ? $this->sendCustomPush($customer, $deviceToken, $customTitle)
            : $this->sendEventNotification($customer);
    }

    /**
     * Direct push with a custom title/body. Bypasses SendNotificationJob because
     * that job only renders booking-derived templates.
     */
    private function sendCustomPush($customer, $deviceToken, $title)
    {
        $body = $this->option('body') ?: '';

        $this->newLine();
        $this->line('Mode      : direct push (custom message)');
        $this->line('Title     : ' . $title);
        $this->line('Body      : ' . $body);

        if (!$deviceToken) {
            $this->error('No usable device token for this customer - nothing to send.');
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run - nothing sent.');
            return self::SUCCESS;
        }

        $response = sendPushNotification($deviceToken, $title, $body);
        $statusCode = $response['status_code'] ?? 0;

        if ($statusCode == 200) {
            $log = new NotificationLog();
            $log->customer_id  = $customer->customer_id;
            $log->type         = 2; // 2 = push
            $log->status       = 1;
            $log->event_type   = 'manual_cli';
            $log->message_text = $body;
            $log->save();

            $this->info('Push sent successfully.');
            return self::SUCCESS;
        }

        $this->error('Push failed (status ' . $statusCode . ').');
        $this->line(json_encode($response['response'] ?? [], JSON_PRETTY_PRINT));
        $this->warn('If this shows NO_ACCESS_TOKEN, the FCM service account JSON is missing from config/.');

        return self::FAILURE;
    }

    /**
     * Booking-driven notification via SendNotificationJob (email + push),
     * matching exactly how the application dispatches these events.
     */
    private function sendEventNotification($customer)
    {
        $event     = $this->option('event');
        $bookingId = $this->option('booking');

        $allowed = ['new_booking', 'extension', 'completion', 'penalty', 'doc_upload_reminder'];
        if (!in_array($event, $allowed, true)) {
            $this->error('Invalid --event. Allowed: ' . implode(', ', $allowed));
            return self::FAILURE;
        }

        if (!$bookingId) {
            $this->error('--booking is required for event notifications.');
            $this->line('The job renders vehicle/pickup details from the booking and aborts without one.');
            $this->line('For a free-text message instead, use: --title="..." --body="..."');
            return self::FAILURE;
        }

        $booking = RentalBooking::where('booking_id', $bookingId)->first();
        if (!$booking) {
            $this->error('Booking not found: ' . $bookingId);
            return self::FAILURE;
        }

        if ($booking->customer_id != $customer->customer_id) {
            $this->error('Booking ' . $bookingId . ' belongs to customer #' . $booking->customer_id
                . ', not #' . $customer->customer_id . '.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Mode      : SendNotificationJob (email + push)');
        $this->line('Booking   : #' . $booking->booking_id);
        $this->line('Event     : ' . $event);

        // The job no-ops unless this gate is 'live'.
        $env = config('global_values.environment');
        if ($env !== 'live') {
            $this->warn('global_values.environment is "' . $env . '" (not "live") - the job will silently send nothing.');
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run - nothing sent.');
            return self::SUCCESS;
        }

        if ($this->option('now')) {
            // Run inline so it works even with no queue worker running.
            SendNotificationJob::dispatchSync($customer->customer_id, $booking->booking_id, $event);
            $this->info('Job executed synchronously.');
            $this->line('Check notification_logs to confirm delivery.');
            return self::SUCCESS;
        }

        SendNotificationJob::dispatch($customer->customer_id, $booking->booking_id, $event)
            ->onQueue('emails');

        $this->info('Job queued on "emails".');
        $this->warn('It stays pending until a worker runs: php artisan queue:work --queue=emails');

        return self::SUCCESS;
    }

    /**
     * Accept a customer ID, email or mobile number for convenience.
     */
    private function resolveCustomer($value)
    {
        if (is_numeric($value) && strlen((string) $value) <= 10) {
            $customer = Customer::where('customer_id', $value)->first();
            if ($customer) {
                return $customer;
            }
        }

        return Customer::where('email', $value)
            ->orWhere('mobile_number', $value)
            ->first();
    }
}
