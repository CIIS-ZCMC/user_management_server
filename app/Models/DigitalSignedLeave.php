<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalSignedLeave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_profile_id',
        'digital_certificate_id',
        'leave_attachment_id',
        'leave_application_id',
        'file_name',
        'file_path',
        'signer_type',
        'status',
        'signing_details',
        'signed_at'
    ];

    protected $casts = [
        'signing_details' => 'array',
        'signed_at' => 'datetime',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function digitalCertificate(): BelongsTo
    {
        return $this->belongsTo(DigitalCertificate::class);
    }

    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }

    public function leaveAttachment(): BelongsTo {
        return $this->belongsTo(LeaveAttachment::class);
    }
}
