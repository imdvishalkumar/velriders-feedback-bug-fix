<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\NotificationLog;
use App\Models\Customer;
use App\Models\CustomerDeviceToken;
use App\Models\RentalBooking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customerId;
    protected $bookingId;
    protected $eventType;
    protected $attachment;
    /**
     * Create a new job instance.
     */
    public function __construct($customer_id, $booking_id, $event_type = NULL, $attachment = [])
    {
        $this->customerId = $customer_id;
        $this->bookingId = $booking_id;
        $this->eventType = $event_type;
        $this->attachment = $attachment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //php artisan queue:work --queue=emails <- Command to run Queue Job
        $emailStatus = false;
        $pushNotificationStatus = false;
        $customer = Customer::where('customer_id', $this->customerId)->first();
        //if ($customer != '' && isset($customer->email) && $customer->email_verified_at != NULL) {
        if ($customer != '' && isset($customer->email)) {
            $emailStatus = true;
            if($this->eventType == 'doc_upload_reminder') {
                $emailStatus = false;
            }
        }
        $customerDeviceToken = CustomerDeviceToken::where(['customer_id' => $customer->customer_id, 'is_deleted' => 0, 'is_error' => 0])->first();
        $deviceToken = $customerDeviceToken->device_token ?? '';
        if ($customer != '' && $deviceToken != '') {
        //if ($customer != '' && isset($customer->device_token) && $customer->device_token != NULL) {
            $pushNotificationStatus = true;
        }
        
        //Send Email to Customer
        $env = config('global_values.environment');
        if($env != '' && $env == 'live'){
            $bookingId = $this->bookingId; // Assuming you have booking ID stored in this property
            $rentalBooking = RentalBooking::with('vehicle')->where('booking_id', $bookingId)->first();
            if ($emailStatus == true) {
                try {
                    $from = 'support@velriders.com';
                    $to = $customer->email;
                    $userName = $customer->firstname . ' ' . $customer->lastname;
                    $templateFile = $subject = '';

                    if ($this->eventType == 'new_booking') {
                        $templateFile = 'emails.front.booking-confirmation';
                        $subject = 'Booking Confirmation Mail';
                    } elseif ($this->eventType == 'extension') {
                        $templateFile = 'emails.front.booking-extension';
                        $subject = 'Booking Extension Mail';
                    } elseif ($this->eventType == 'completion') {
                        $templateFile = 'emails.front.booking-completion';
                        $subject = 'Booking Completion Mail';
                    } 
                    $attach = $this->attachment;
                    Mail::send($templateFile, ['to' => $to, 'name' => $userName], function ($m) use ($from, $to, $subject, $attach) {
                        $m->from($from);
                        $m->to($to)->subject($subject);
                        // if (count($attach) > 0) {
                        //     foreach ($attach as $attachment) {
                        //         $m->attach($attachment);
                        //     }
                        // }
                    });
                    // if($this->eventType == 'new_booking'){
                    //     if(isset($attach) && is_countable($attach) && count($attach) > 0){
                    //         $rentalBooking->is_aggrement_send = 1;
                    //         $rentalBooking->save();
                    //     }
                    // }

                    $notificationLog = new NotificationLog();
                    $notificationLog->customer_id = $customer->customer_id;
                    $notificationLog->type = 1; // 2 Means Email notification
                    $notificationLog->status = 1;
                    $notificationLog->event_type = $subject;
                    $notificationLog->save();

                    //Log::info($this->eventType . " Mail Notification Sent Successfully");
                } catch (\Exception $e) {}
            }

            //Send Push Notification to Customer
            if ($pushNotificationStatus == true) {
                try {
                    $title = $content = '';
                    if ($this->eventType == 'new_booking') {
                        $title = 'Booking Confirmation';
                        $content = 'Your Booking is Confirmed';
                    } elseif ($this->eventType == 'extension') {
                        $title = 'Booking Extension';
                        $content = 'Your Booking Extesion is Done';
                    } elseif ($this->eventType == 'completion') {
                        $title = 'Booking Completion';
                        $content = 'Your Booking is Completed..';
                    } elseif ($this->eventType == 'penalty') {
                        $title = 'Booking Penalty';
                        $content = 'Your Penalty is Paid..';
                    }
                    if ($rentalBooking) {
                        // Assuming rentalBooking has pickup_date and return_date fields
                        $pickupDate = new Carbon($rentalBooking->pickup_date);
                        $returnDate = new Carbon($rentalBooking->return_date);
                        $vehicleName = $rentalBooking->vehicle->vehicle_name;

                        if ($this->eventType == 'new_booking') {
                            $title = 'Vehicle Booking Confirmation';
                            $content = "We’re excited to confirm your booking (ID: $bookingId) for the vehicle $vehicleName. Pickup date and time: {$pickupDate->format('d-m-Y h:i A')}. Return date and time: {$returnDate->format('d-m-Y h:i A')}. Thank you for choosing our service.";
                        } elseif ($this->eventType == 'extension') {
                            // Calculate the extended return date/time
                            $extendedReturnDate = clone $returnDate;
                            $extendedReturnDate->setTimezone('Asia/Kolkata');

                            $title = 'Vehicle Booking Extension';
                            $content = "Your booking (ID: $bookingId) for the vehicle $vehicleName has been successfully extended until {$extendedReturnDate->format('d-m-Y h:i A')}. Enjoy the additional time!";
                        } elseif ($this->eventType == 'completion') {
                            $title = 'Vehicle Booking Completion';
                            $content = "Your booking (ID: $bookingId) for the vehicle $vehicleName is now complete. We hope you had a pleasant journey!";
                        } elseif ($this->eventType == 'doc_upload_reminder'){
                            $title = 'Document Upload Reminder';
                            $content = "Your document is not uploaded yet. Kindly upload Before your ride start";
                        } elseif ($this->eventType == 'penalty') {
                            $title = 'Booking Penalty';
                            $content = "Your Penalty is Paid..";
                        } 
                        
                        //$notificationResponse = sendPushNotification($customer->device_token, $title, $content);
                        $notificationResponse = sendPushNotification($deviceToken, $title, $content);
                        if (isset($notificationResponse['status_code']) && $notificationResponse['status_code'] == 200) {
                            $notificationLog = new NotificationLog();
                            $notificationLog->customer_id = $customer->customer_id;
                            $notificationLog->type = 2; // 2 Means Push notification
                            $notificationLog->status = 1;
                            $notificationLog->event_type = $this->eventType;
                            $notificationLog->message_text = $content;
                            $notificationLog->save();
                            //Log::info($this->eventType . " Push Notification Sent Successfully -" . json_encode($notificationResponse['response']));
                        } else {
                            Log::error($this->eventType . ' Something went wrong.. Notification not sent to this number - ' . $customer->mobile_number);
                        }
                    } else {
                        // Handle case where no booking is found
                        $title = 'Error';
                        $content = "Booking with ID $bookingId not found.";
                        Log::error($this->eventType . ' $content Something went wrong.. Notification not sent to this number - ' . $customer->mobile_number);
                    }
                } catch (\Exception $e) {}
            }
        }
    }
}
