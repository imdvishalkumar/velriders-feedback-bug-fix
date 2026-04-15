<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDocument extends Model
{
    use HasFactory;

    protected $primaryKey = 'document_id';

    protected $fillable = [
        'customer_id',
        'vehicle_id',
        'document_type',
        'id_number',
        'expiry_date',
        'is_approved',
        'approved_by',
        'document_image_url',
        'document_back_image_url',
        'custom_rejection_message',
        'rejection_message_id',
        'vehicle_type',
        'is_approved_datetime',
        'cashfree_api_response',
        'is_blocked',
        'govtid_type',
        'dob'
    ];

    protected $hidden = [
        'rejection_message',
        'rejection_reason',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['message', 'icon', 'color', 'status_name', 'car', 'bike'];


    // Define the relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(AdminUser::class, 'approved_by', 'admin_id');
    }

    public function rejectionReason()
    {
        return $this->belongsTo(RejectionReason::class, 'rejection_message_id', 'id');
    }

    public function getDocumentImageUrlAttribute()
    {
        if ($this->attributes['document_image_url']) {
            return asset('images/customer_documents/' . $this->attributes['document_image_url']);
        }
        return null;
    }

    public function getDocumentBackImageUrlAttribute()
    {
        if ($this->attributes['document_back_image_url']) {
            return asset('images/customer_documents/' . $this->attributes['document_back_image_url']);
        }
        return null;
    }

    public function getMessageAttribute()
    {
        if ($this->is_approved == 'approved') {
            return 'Your document has been approved.';
        } elseif ($this->is_approved == 'rejected') {
            if (isset($this->attributes['custom_rejection_message']) && !empty($this->attributes['custom_rejection_message'])) {
                return $this->attributes['custom_rejection_message'];
            } elseif (is_object($this->rejectionReason)) {
                return $this->rejectionReason->reason;
            } else {
                return 'Your document has been rejected. Please re-upload.';
            }
        } elseif ($this->is_approved == 'pending' || $this->is_approved == 'awaiting_approval') {
            return 'Your document is awaiting approval.';
        } elseif ($this->is_approved == 'not applied') {
            return 'You have not applied yet. Please upload your document.';
        } else {
            // Default case
            return 'Please upload your document.';
        }
    }
    
    public function getColorAttribute()
    {
        if ($this->is_approved == 'approved') {
            return "#006400"; // Dark green
        } else if ($this->is_approved == 'rejected') {
            return "#8B0000"; // Dark red
        } else if ($this->is_approved == 'pending' || $this->is_approved == 'awaiting_approval') {
            return "#FFA500"; // Gold
        } else if ($this->is_approved == 'not_applied') {
            return "#A9A9A9"; // Dark gray
        } else {
            return "#000000"; // Black
        }
    }
    public function getIconAttribute()
    {
        if ($this->is_approved == 'approved') {
            return url('images/icon/approved.svg');
        } else if ($this->is_approved == 'rejected') {
            return url('images/icon/Rejected.svg');
        } else if ($this->is_approved == 'pending' || $this->is_approved == 'awaiting_approval') {
            return url('images/icon/pending.svg');
        } else if ($this->is_approved == 'not_applied') {
            return url('images/icon/not_applied.svg');
        } else {
            return url('images/icon/not_applied.svg');
        }
    }

    public function getStatusNameAttribute()
    {
        if ($this->is_approved == 'approved') {
            return "Approved";
        } else if ($this->is_approved == 'rejected') {
            return "Rejected";
        } else if ($this->is_approved == 'pending' || $this->is_approved == 'awaiting_approval') {
            return "Awaiting Approval";
        } else {
            return "Not Applied";
        }
    }

    public function getCarAttribute()
    {
        return strpos($this->vehicle_type, 'car') !== false;
    }

    public function getBikeAttribute()
    {
        return strpos($this->vehicle_type, 'bike') !== false;
    }

    public function getDocumentStatus()
    {
        return [
            "status_name" => $this->status_name,
            "icon" => $this->icon,
            "color" => $this->color,
            "message" => $this->message,
        ];
    }
}
