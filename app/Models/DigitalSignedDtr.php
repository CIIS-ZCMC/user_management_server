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
        'file_name',
        'file_path',
        'signer_type',
        'whole_month',
        'month_year',
        'status',
        'signing_details',
        'signed_at',
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
}
