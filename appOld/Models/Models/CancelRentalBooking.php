<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancelRentalBooking extends Model
{
    use HasFactory;
    protected $primaryKey = 'cancel_id';
    protected $guarded = [];

    public function rentalBooking()
    {
        return $this->belongsTo(RentalBooking::class, 'booking_id', 'booking_id');
    }

    public function refund()
    {
        return $this->belongsTo(Refund::class, 'booking_id', 'booking_id');
    }

}
