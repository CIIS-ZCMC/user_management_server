<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateDetails extends Model
{
    use HasFactory;

    protected $table = 'certificate_details';
    protected $fillable = [
        'employee_profile_id',
        'certificate_attachment_id',
        'subject_owner',
        'issued_by',
        'organization_unit',
        'country',
        'valid_from',
        'valid_till',
        'public_key',
        'private_key',
    ];

    public function employeeProfile(): BelongsTo {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
