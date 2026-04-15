<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripAmountCalculationRule extends Model
{
    use HasFactory;

    protected $fillable = ['hours', 'multiplier'];

}
