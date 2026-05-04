<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancelRentalBooking extends Model
{
    use HasFactory;
    protected $primaryKey = 'cancel_id';
    protected $guarded = [];
    protected $casts = [
        'data_json' => 'array',
    ];

    public function rentalBooking()
    {
        return $this->belongsTo(RentalBooking::class, 'booking_id', 'booking_id');
    }

    public function refund()
    {
        return $this->belongsTo(Refund::class, 'booking_id', 'booking_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(AdminUser::class, 'cancelled_by', 'admin_id');
    }

}
