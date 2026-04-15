<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleImage extends Model
{
    protected $primaryKey = 'image_id';

    protected $fillable = [
        'vehicle_id',
        'image_url',
        'is_banner',
    ];

    protected $hidden = [
        'image_id',
        'vehicle_id',
        'created_at',
        'updated_at',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    public function getIsBannerAttribute($value)
    {
        return (bool) $value;
    }

    public function getImageUrlAttribute()
    {
        if ($this->attributes['image_url']) {
            return asset('images/vehicle_images/' . $this->attributes['image_url']);
        }
        return null;
    }

}
