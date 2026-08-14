<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'amount',
        'payment_date',
    ];
}
