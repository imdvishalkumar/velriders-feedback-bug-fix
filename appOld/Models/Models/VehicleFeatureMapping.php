<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleFeatureMapping extends Model
{
    protected $primaryKey = 'mapping_id';

    protected $fillable = [
        'vehicle_id',
        'feature_id',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    public function feature()
    {
        return $this->belongsTo(VehicleFeature::class, 'feature_id', 'feature_id');
    }
}
