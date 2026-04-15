<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReferralDetails extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function customerDetails()
    {
        return $this->hasOne(Customer::class, 'customer_id', 'customer_id');
    }

    public function referredUser()
    {
        return $this->hasOne(Customer::class, 'my_referral_code', 'used_referral_code');
    }   
    
}
