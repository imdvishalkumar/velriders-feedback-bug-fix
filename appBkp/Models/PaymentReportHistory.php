<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReportHistory extends Model
{
    use HasFactory;

    protected $table = 'payment_report_history';

    protected $fillable = [
        'booking_id',
        'session_id',
        'export_data',
        'is_completed',
        'exported_at',
        'export_filters',
    ];

    protected $casts = [
        'export_data' => 'array',
        'export_filters' => 'array',
        'is_completed' => 'boolean',
        'exported_at' => 'datetime',
    ];

    // Relationship with RentalBooking
    public function booking()
    {
        return $this->belongsTo(RentalBooking::class, 'booking_id', 'booking_id');
    }
}
