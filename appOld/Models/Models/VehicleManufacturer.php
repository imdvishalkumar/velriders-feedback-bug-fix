<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleManufacturer extends Model
{
    protected $primaryKey = 'manufacturer_id';

    protected $fillable = [
        'vehicle_type_id',
        'name',
        'logo',
    ];

    protected $hidden = [
        'is_deleted',
        'created_at',
        'updated_at',
    ];

    public function models()
    {
        return $this->hasMany(VehicleModel::class, 'manufacturer_id', 'manufacturer_id');
    }

    public function getLogoAttribute()
    {
        if ($this->attributes['logo']) {
            return asset('images/vehicle_manufacturers/' . $this->attributes['logo']);
        }
        return null;
    }

}
