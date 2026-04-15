<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleUpdateHistory extends Model
{
    use HasFactory;

    protected $table = 'vehicle_update_history';

    protected $fillable = [
        'booking_id',
        'old_vehicle_id',
        'new_vehicle_id',
        'change_reason',
        'change_datetime',
        'updated_by',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(RentalBooking::class, 'booking_id', 'booking_id');
    }

    public function oldVehicle()
    {
        return $this->belongsTo(Vehicle::class, 'old_vehicle_id', 'vehicle_id');
    }

    public function newVehicle()
    {
        return $this->belongsTo(Vehicle::class, 'new_vehicle_id', 'vehicle_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(AdminUser::class, 'updated_by', 'admin_id');
    }
}
