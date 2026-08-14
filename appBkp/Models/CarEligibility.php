<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarEligibility extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['manager_name', 'phone'];
    
    public function carHost()
    {
        return $this->belongsTo(CarHost::class, 'car_hosts_id', 'id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    public function vehiclePickupLocation()
    {
        return $this->hasOne(CarHostPickupLocation::class, 'id', 'car_host_pickup_location_id');
    }

    public function nightHours()
    {
        return $this->belongsTo(NightHour::class, 'night_hours_id');
    }


    public function getManagerNameAttribute()
    {
        $managerName = '';
        $carEligibility = CarEligibility::where('vehicle_id', $this->vehicles_id)->first();
        if($carEligibility != '' && $carEligibility->carHost){
            $managerName = $carEligibility->carHost->firstname ?? '';
            $managerName .= ' '. $carEligibility->carHost->lastname ?? '';
        }

        return $managerName;
    }

    public function getPhoneAttribute()
    {
        $phone = '';
        $carEligibility = CarEligibility::where('vehicle_id', $this->vehicles_id)->first();
        if($carEligibility != '' && $carEligibility->carHost){
            $phone = $carEligibility->carHost->mobile_number ?? '';
        }

        return $phone;
    }
}
