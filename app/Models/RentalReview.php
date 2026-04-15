<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalReview extends Model
{
    use HasFactory;

    protected $primaryKey = 'review_id';

    protected $fillable = ['vehicle_id','booking_id', 'customer_id', 'rating', 'review_text'];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    // public function vehicle()
    // {
    //     return $this->belongsTo(Vehicle::class);
    // }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

}
