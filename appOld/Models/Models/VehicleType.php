<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    use HasFactory;

    protected $primaryKey = 'type_id'; // Specify the primary key field name
    protected $fillable = [
        'name',
        'convenience_fee'
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    // Optionally, you can define any custom logic, relationships, or methods here

    /**
     * Get the categories associated with the vehicle type.
     */
    // protected $appends = ['categories_list'];

    // public function getCategoriesListAttribute()
    // {
    //     $category = VehicleCategory::where('vehicle_type_id', $this->type_id)->first();
    //     return $category;
    // }

    public function categories()
    {
        return $this->hasMany(VehicleCategory::class, 'vehicle_type_id', 'type_id');
    }
}
