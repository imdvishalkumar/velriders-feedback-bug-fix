<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $primaryKey = 'refund_id';

    protected $fillable = [
        'booking_id',
        'payment_id',
        'refund_amount',
        'status',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
