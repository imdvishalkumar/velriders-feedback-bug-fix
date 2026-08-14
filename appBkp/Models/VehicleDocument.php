<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleDocument extends Model
{
    use HasFactory;

    protected $primaryKey = 'document_id'; // Specify the primary key field name
    protected $guarded = [];

    

}
