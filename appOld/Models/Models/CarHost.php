<?php

namespace App\Models;

//use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
//use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class CarHost extends Authenticatable implements JWTSubject
{
     //use HasApiTokens, HasFactory, Notifiable;
     use Notifiable;

       protected $fillable = [
        'country_code',
        'mobile_number',
        'email',
        'firstname',
        'lastname',
        'dob',
        'profile_picture_url',
        'billing_address',  
        'shipping_address',
        'business_name',
        'gst_number',
        'is_deleted',
        'gauth_id',
        'gauth_type',
        'registered_via',
    ];

    protected $hidden = [
        'otp',
        // 'is_deleted',
        'created_at',
        'updated_at',
    ];

    protected $table = 'car_hosts';

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'mobile_number' => $this->mobile_number
        ];
    }

    public function getProfilePictureUrlAttribute()
    {
        if ($this->attributes['profile_picture_url']) {
            return asset('images/profile_pictures/' . $this->attributes['profile_picture_url']);
        }
        return null;
    }

    public function getDobAttribute()
    {
        $dob = isset($this->attributes['dob']) ? date('d-m-Y', strtotime($this->attributes['dob'])) : '';
        return $dob;
    }
    
    /*public function loginTokens()
    {
        return $this->morphMany(LoginToken::class, 'customer');
    }*/
}
