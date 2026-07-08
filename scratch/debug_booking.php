<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BookingTransaction;
use App\Models\RentalBooking;

$bookingId = 14193;
$booking = RentalBooking::where('booking_id', $bookingId)->first();
if (!$booking) {
    echo "Booking not found\n";
    exit;
}

echo "Booking Status: " . $booking->status . "\n";
echo "Total Price: " . $booking->total_price . "\n";

$transactions = BookingTransaction::where('booking_id', $bookingId)->get();
foreach ($transactions as $t) {
    echo "Transaction ID: {$t->id}, Type: {$t->type}, Paid: {$t->paid}, Trip Amt: {$t->trip_amount}, Convenience: {$t->convenience_fee}, Tax: {$t->tax_amt}, Total: {$t->total_amount}, Final: {$t->final_amount}, Amount to Pay: {$t->amount_to_pay}, Late Return: {$t->late_return}\n";
}
