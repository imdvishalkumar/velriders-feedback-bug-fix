<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarHostVehicleFeatureTemp extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function feature()
    {
        return $this->belongsTo(VehicleFeature::class, 'feature_id', 'feature_id');
    }

    public function vehicle()
    {
        //return $this->belongsTo(CarEligibility::class, 'vehicle_id', 'vehicles_id');
        return $this->hasOne(CarEligibility::class, 'vehicle_id', 'vehicles_id');
    }

    public function vehicleDetails()
    {
        return $this->hasOne(Vehicle::class, 'vehicle_id', 'vehicles_id');
    }
}
