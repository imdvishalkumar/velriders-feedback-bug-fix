<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $table = 'admin_activity_log';
    protected $primaryKey = 'log_id';

    public function adminDetails()
    {
        return $this->belongsTo(AdminUser::class, 'admin_id', 'admin_id');
    }
}
