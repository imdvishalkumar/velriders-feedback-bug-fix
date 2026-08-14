<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleFeature extends Model
{
    protected $primaryKey = 'feature_id';

    protected $fillable = [
        'name',
        'icon',
        'created_at',
        'is_deleted',
        'feature_id',
    ];
    protected $hidden = [
        'pivot',
        'is_deleted',
        'updated_at',
    ];

    public function getIconAttribute()
    {
        if ($this->attributes['icon']) {
            return asset('images/vehicle_features/' . $this->attributes['icon']);
        }
        return null;
    }
}
