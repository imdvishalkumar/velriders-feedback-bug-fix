<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarHostVehicleImage extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $appends = ['carhost_parking_img', 'vehicle_img'];

    public function getCarHostParkingImgAttribute()
    {
        if ($this->attributes['vehicle_img']) {
            return asset('images/car_host/' . $this->attributes['vehicle_img']);
        }
        return null;
    }

    public function getVehicleImgAttribute()
    {
        if ($this->attributes['vehicle_img']) {
            return asset('images/car_host/' . $this->attributes['vehicle_img']);
        }
        return null;
    }

}
