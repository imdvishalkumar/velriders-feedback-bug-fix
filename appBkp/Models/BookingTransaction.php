<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\RentalBooking;

class BookingTransaction extends Model
{
    protected $fillable = [
        'booking_id',
        'timestamp',
        'type',
        'start_date',
        'end_date',
        'unlimited_kms',
        'rental_price',
        'trip_duration_minutes',
        'trip_amount',
        'tax_amt',
        'coupon_discount',
        'coupon_code',
        'trip_amount_to_pay',
        'convenience_fee',
        'total_amount',
        'refundable_deposit',
        'final_amount',
        'order_type',
        'payment_mode',
        'reference_number',
        'paid',
        'razorpay_order_id',
        'razorpay_payment_id',
        'from_refundable_deposit',
        'late_return',
        'exceeded_km_limit',
        'additional_charges',
        'additional_charges_info',
        'amount_to_pay',
        'refundable_deposit_used',
        'cashfree_order_id', 
        'cashfree_payment_session_id',
        'is_deleted',
        'vehicle_commission_amount', 
        'vehicle_commission_tax_amt',
        'icici_merchant_txnNo',
        'icici_txnid',
    ];

    public function rentalBooking()
    {
        return $this->belongsTo(RentalBooking::class, 'booking_id', 'booking_id');
    }
}
