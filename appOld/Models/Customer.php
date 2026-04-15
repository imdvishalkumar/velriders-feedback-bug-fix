<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Carbon\Carbon;
use DB;
use App\Models\CustomerDeviceToken;

class Customer extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $table = 'customers';
    
    protected $primaryKey = 'customer_id';

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
        'is_blocked',
        'gauth_id',
        'gauth_type',
        'device_token',
        'is_guest_user', 
        'registered_via',
        'dl_doc_verification_cnt',
        'govt_doc_verification_cnt',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'otp',
        // 'is_deleted',
        'created_at',
        'updated_at',
        'passbook_image'
    ];

    protected $appends = ['documents', 'passbook_image_url'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'mobile_number_verified_at' => 'datetime',
    ];

    public function getPassbookImageUrlAttribute()
    {
        if ($this->passbook_image) {
            return url($this->passbook_image);
        }
        return null;
    }

    public function getDocumentsAttribute()
    {
        /*$documentDL = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'dl')->latest('is_approved_datetime')->first();
        $documentGI = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'govtid')->latest('is_approved_datetime')->first();*/

        //->orderBy(DB::raw('COALESCE(is_approved_datetime, updated_at)'), 'DESC') = It will return is_approved_datetime if it's not null; otherwise, it will return updated_at.
        $documentDL = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'dl')->orderBy(DB::raw('COALESCE(is_approved_datetime, updated_at)'), 'DESC')->first();
        $documentGI = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'govtid')->orderBy(DB::raw('COALESCE(is_approved_datetime, updated_at)'), 'DESC')->first();

        if ($documentDL === null) {
            $documentDL = new CustomerDocument();
        }
        if ($documentGI === null) {
            $documentGI = new CustomerDocument();
        }
        
        return ['dl' => $documentDL->status_name, 'govtid' => $documentGI->status_name];

    }
    public function getDobAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d-m-Y') : null;
    }
    
    /*public function setDobAttribute($value)
    {
        $this->attributes['dob'] = $value ? Carbon::createFromFormat('d-m-Y', $value)->toDateString() : null;
    }*/

    public function getProfilePictureUrlAttribute()
    {
        if ($this->attributes['profile_picture_url']) {
            return asset('images/profile_pictures/' . $this->attributes['profile_picture_url']);
        }
        return null;
    }

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

    public function customerDeviceToken()
    {
        return $this->hasMany(customerDeviceToken::class, 'customer_id', 'customer_id');
    }
  
    public function deviceToken()
    {
        return $this->hasMany(customerDeviceToken::class, 'customer_id', 'customer_id')->where(['is_deleted' => 0, 'is_error' => 0]);
    }


    // public function userLocationDetails()
    // {
    //     return $this->hasMany(UserLocationDetail::class, 'device_token', 'device_token');
    // }

    public function customerDocs()
    {
        return $this->hasMany(CustomerDocument::class, 'customer_id', 'customer_id');
    }
    
}
