<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'booking_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'amount',
        'payment_date',
        'status',
        'payment_gateway_charges',

        'payment_type',
        'payment_mode',
        'transaction_ref_number', 
        'payment_env', 
        'cashfree_order_id', 
        'cashfree_payment_session_id', 
        'payment_gateway_used', 
        'icici_merchant_txnNo',
        'icici_txnid',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    // Define the relationship with the rental booking
    public function booking()
    {
        return $this->belongsTo(RentalBooking::class, 'booking_id', 'booking_id');
    }
}
