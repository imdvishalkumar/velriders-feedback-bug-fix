<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleProperty extends Model
{
    protected $primaryKey = 'property_id';

    protected $fillable = [
        'vehicle_id',
        'mileage',
        'fuel_type_id',
        'transmission_id',
        'seating_capacity',
        'engine_cc',
        'fuel_capacity',
    ];

    protected $hidden = [
        'property_id',
        'is_deleted',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['transmission_name', 'fuel_type_name'];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function fuelType()
    {
        return $this->belongsTo(FuelType::class, 'fuel_type_id');
    }

    public function transmission()
    {
        return $this->belongsTo(Transmission::class, 'transmission_id');
    }


    public function getSeatingCapacityAttribute($value)
    {
        return strval($value);
    }

    public function getTransmissionNameAttribute()
    {
        return $this->transmission->name ?? null;
    }

    public function getFuelTypeNameAttribute()
    {
        return $this->fuelType->name ?? null;
    }

    public function getMileageAttribute($value)
    {
        if ($value == 0) {
            return "";
        } else {
            return $value . ' kmpl';
        }
    }

    public function getEngineCcAttribute($value)
    {
        return $value . ' cc';
    }

    public function getFuelCapacityAttribute($value)
    {
        return $value . ' Ltrs';
    }
}
