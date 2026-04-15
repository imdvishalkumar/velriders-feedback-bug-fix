<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    protected $primaryKey = 'model_id';

    protected $fillable = [
        'name',
        //'manufacturer_id',
        'model_image',
        'min_price', 
        'max_price',
        'min_km_limit', 
        'max_km_limit',
        'min_deposit_amount',
        'max_deposit_amount',
    ];

    protected $hidden = [
        'manufacturer_id',
        'is_deleted',
        'created_at',
        'updated_at',
    ];

    public function manufacturer()
    {
        return $this->belongsTo(VehicleManufacturer::class, 'manufacturer_id', 'manufacturer_id');
    }

    public function category()
    {
        return $this->belongsTo(VehicleCategory::class, 'category_id', 'category_id');
    }

    public function modelPriceSummary()
    {
        return $this->hasMany(VehicleModelPriceDetail::class, 'vehicle_model_id', 'model_id');
    }

    public function getModelImageAttribute()
    {
        if ($this->attributes['model_image']) {
            return asset('images/vehicle_models/' . $this->attributes['model_image']);
        }
        return null;
    }
}
