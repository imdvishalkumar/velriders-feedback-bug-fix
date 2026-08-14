<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    public static function nearest($latitude, $longitude)
    {
        return static::selectRaw("
                *,
                (6371 * acos(
                    cos(radians($latitude)) 
                    * cos(radians(latitude)) 
                    * cos(radians(longitude) - radians($longitude)) 
                    + sin(radians($latitude)) 
                    * sin(radians(latitude))
                )) as distance
            ")
            ->orderBy('distance')
            ->first();
    }

    public static function nearestNew($lat, $lng){
        $radius = 100;
        $cities = City::select('id', 'name', 'latitude', 'longitude')
            ->selectRaw("
            (6371 * acos(
                cos(radians(?)) 
                * cos(radians(latitude)) 
                * cos(radians(longitude) - radians(?)) 
                + sin(radians(?)) 
                * sin(radians(latitude))
            )) AS distance_km
        ", [$lat, $lng, $lat])
        ->having('distance_km', '<=', $radius)
        ->pluck('id')->toArray();
        //->orderByRaw('distance_km ASC')
        //->get();
       
        return $cities;
    }

    public function branch()
    {
        return $this->hasMany(Branch::class, 'city_id', 'id');
    }
}
