<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $primaryKey = 'branch_id'; // Specify the primary key field name
    protected $fillable = [
        'city_id',
        'name',
        'manager_name',
        'address',
        'latitude',
        'longitude',
        'phone',
        'email',
        'opening_hours',
        'is_head_branch'
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    // Method to calculate the distance between two points using the Haversine formula
    public function distanceTo($latitude, $longitude)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latFrom = deg2rad($latitude);
        $lonFrom = deg2rad($longitude);
        $latTo = deg2rad($this->latitude);
        $lonTo = deg2rad($this->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) *
            pow(sin($lonDelta / 2), 2)
        ));

        return $angle * $earthRadius;
    }

    // Method to retrieve nearby branches based on latitude and longitude
    public static function nearby($latitude, $longitude, $radius = 10)
    {
        return static::whereRaw("
            (6371 * acos(
                cos(radians($latitude)) 
                * cos(radians(latitude)) 
                * cos(radians(longitude) - radians($longitude)) 
                + sin(radians($latitude)) 
                * sin(radians(latitude))
            )) < $radius
        ")->get();
    }

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

    public function city()
    {
        return $this->hasOne(City::class, 'id', 'city_id');
    }
}
