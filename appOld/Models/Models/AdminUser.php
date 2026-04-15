<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;

use Laravel\Sanctum\HasApiTokens; // FOR ADMIN API

// LARAVEL ADMIN
/* class AdminUser extends Authenticatable implements AuthorizableContract
{

// use Illuminate\Contracts\Auth\Authenticatable as AuthAuthenticatable;
// use Illuminate\Database\Eloquent\Model;

// class AdminUser extends Model implements AuthAuthenticatable
// {
    use HasFactory,AuthenticatableTrait,HasRoles,Authorizable;

    protected $primaryKey = 'admin_id';
    protected $fillable = [
        'username',
        'password',
        'role',
    ];

}*/

// VUE ADMIN ADMIN
class AdminUser extends Authenticatable
{
    use HasFactory, HasApiTokens, HasRoles;
    
    protected $primaryKey = 'admin_id';
    protected $fillable = [
        'username',
        'password',
        'role',
        'mobile_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}