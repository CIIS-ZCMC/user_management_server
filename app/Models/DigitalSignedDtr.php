<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class DigitalSignedDtr extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_profile_id',
        'digital_certificate_id',
        'digital_dtr_signature_request_id',
        'file_name',
        'file_path',
        'signer_type',
        'whole_month',
        'status',
        'signing_details',
        'signed_at',
    ];

    protected $casts = [
        'signing_details' => 'array',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function digitalCertificate(): BelongsTo
    {
        return $this->belongsTo(DigitalCertificate::class);
    }

    public function digitalDtrSignatureRequest(): BelongsTo
    {
        return $this->belongsTo(DigitalDtrSignatureRequest::class);
    }
}
