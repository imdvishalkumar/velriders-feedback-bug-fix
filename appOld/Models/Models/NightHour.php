<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NightHour extends Model
{
    protected $hidden = [ 'created_at', 'updated_at'];
    protected $fillable = [
        'title',
        'description',
    ];

}
