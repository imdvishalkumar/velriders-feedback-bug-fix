<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transmission extends Model
{
    protected $table = 'vehicle_transmissions'; // Specify the table name

    protected $primaryKey = 'transmission_id';

    protected $fillable = [
        'name',
        'vehicle_type_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    
    public function properties()
    {
        return $this->hasMany(VehicleProperty::class, 'transmission_id');
    }

    public function getVehicleType()
    {
        return $this->hasOne(VehicleType::class, 'type_id', 'vehicle_type_id');
    }
}
