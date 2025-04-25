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
        'tracking_code',
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

    /**
     * Boot function from Laravel.
     */
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->tracking_code)) {
                $model->tracking_code = $model->generateUniqueTrackingCode();
            }
        });
    }

    /**
     * Generate a unique tracking code for this document.
     * Format: [employee_id]-[5 digit unique code]
     *
     * @return string
     */
    public function generateUniqueTrackingCode()
    {
        $employee_profile_id = $this->employee_profile_id;
        $employee_id = EmployeeProfile::where('id', $employee_profile_id)->first()->id;
        $is_unique = false;
        $tracking_code = '';

        while (!$is_unique) {
            // Generate a random 5-digit code
            $random_code = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);

            // Create the tracking code in the required format
            $tracking_code = $employee_id . '-' . $random_code;

            // Check if this code already exists
            $exists = self::where('tracking_code', $tracking_code)->exists();

            if (!$exists) {
                $is_unique = true;
            }
        }

        return $tracking_code;
    }

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

    public function leaveAttachment(): BelongsTo
    {
        return $this->belongsTo(LeaveAttachment::class);
    }
}
