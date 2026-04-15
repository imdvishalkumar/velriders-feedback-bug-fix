<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarHostPickupLocation extends Model
{
    use HasFactory;

    protected $guarded = [];

    // protected $appends = ['manager_name', 'phone'];

    public function carHostParkingVehicleImgs()
    {
        return $this->hasMany(CarHostVehicleImage::class, 'car_host_pickup_locations_id', 'id')
            ->where(function ($query) {
                $query->where('image_type', 1);
            });
    }

    // public function carEligibility()
    // {
    //     return $this->hasOne(CarEligibility::class, 'vehicle_id', 'vehicles_id');
    // }

    // public function getManagerNameAttribute()
    // {
    //     $managerName = '';
    //     $carEligibility = CarEligibility::where('vehicle_id', $this->vehicles_id)->first();
    //     if($carEligibility != '' && $carEligibility->carHost){
    //         $managerName = $carEligibility->carHost->firstname ?? '';
    //         $managerName .= ' '. $carEligibility->carHost->lastname ?? '';
    //     }

    //     return $managerName;
    // }

    // public function getPhoneAttribute()
    // {
    //     $phone = '';
    //     $carEligibility = CarEligibility::where('vehicle_id', $this->vehicles_id)->first();
    //     if($carEligibility != '' && $carEligibility->carHost){
    //         $phone = $carEligibility->carHost->mobile_number ?? '';
    //     }

    //     return $phone;
    // }


    public function city()
    {
        return $this->hasOne(City::class, 'id', 'city_id');
    }


}
