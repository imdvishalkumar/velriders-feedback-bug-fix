<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalBookingImage extends Model
{
    protected $fillable = [
        'booking_id',
        'image_url',
        'image_type',
    ];

    protected $hidden = [
        'id',
        'booking_id',
        'image_type',
        'created_at',
        'updated_at',
    ];

    public function getImageUrlAttribute()
    {
        if ($this->attributes['image_url']) {
            return asset('images/rental_booking_images/' . $this->attributes['image_url']);
        }
        return null;
    }


    // Define relationships
    public function rentalBooking()
    {
        return $this->belongsTo(RentalBooking::class);
    }
}
