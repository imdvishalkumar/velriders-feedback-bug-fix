<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarHostPickupLocationTemp extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function carHostParkingVehicleImgs()
    {
        return $this->hasMany(CarHostVehicleImageTemp::class, 'car_host_pickup_locations_id', 'car_host_pickup_locations_id')
                ->where(function ($query) {
                    $query->where('image_type', 1);
                });
    }
    
    public function carHost()
    {
        return $this->belongsTo(CarHost::class, 'car_hosts_id', 'id');
    }

}
