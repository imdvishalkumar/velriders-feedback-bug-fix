<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleCategory extends Model
{
    use HasFactory;

    protected $primaryKey = 'category_id'; // Specify the primary key field name
    protected $fillable = [
        'vehicle_type_id',
        'name',
        'icon',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function getIconAttribute()
    {
        if ($this->attributes['icon']) {
            return asset('images/vehicle_categories/' . $this->attributes['icon']);
        }
        return null;
    }

    // Optionally, you can define any custom logic, relationships, or methods here

    /**
     * Get the vehicle type associated with the category.
     */
    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id', 'type_id');
    }

    public function getVehicleType()
    {
        return $this->hasOne(VehicleType::class, 'type_id', 'vehicle_type_id');
    }

}
