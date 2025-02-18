<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalCertificate extends Model
{
    use HasFactory;

    protected $table = "digital_certificates";

    protected $fillable = [
        'employee_profile_id',
        'digital_certificate_file_id',
        'subject_owner',
        'issued_by',
        'organization_unit',
        'country',
        'valid_from',
        'valid_to',
        'public_key',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    /**
     * Get the digital certificate file that owns the digital certificate.
     */
    public function digitalCertificateFile(): BelongsTo
    {
        return $this->belongsTo(DigitalCertificateFile::class);
    }
}
