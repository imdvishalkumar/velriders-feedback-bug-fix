<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarHostVehicleFeature extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function feature()
    {
        return $this->belongsTo(VehicleFeature::class, 'feature_id', 'feature_id');
    }
}
