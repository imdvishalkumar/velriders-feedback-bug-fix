<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelType extends Model
{
    protected $table = 'vehicle_fuel_types'; // Specify the table name

    protected $primaryKey = 'fuel_type_id';

    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function properties()
    {
        return $this->hasMany(VehicleProperty::class, 'fuel_type_id');
    }

    public function vehicleType()
    {
        return $this->hasMany(VehicleType::class, 'type_id');
    }

    public function getVehicleType()
    {
        return $this->hasOne(VehicleType::class, 'type_id', 'vehicle_type_id');
    }
}
