<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class RejectionReason extends Model
{
    use HasFactory;

    protected $table = 'rejection_messages';

    protected $fillable = ['reason'];

    // You can define relationships or other logic here as needed
}
