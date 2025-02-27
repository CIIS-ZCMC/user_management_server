<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DigitalDtrSignatureRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_profile_id',
        'employee_head_profile_id',
        'digital_certificate_id',
        'dtr_date',
        'status',
        'remarks',
        'approved_at'
    ];

    protected $table = 'digital_dtr_signature_requests';

    protected $casts = [
        'dtr_date' => 'date',
        'approved_at' => 'datetime'
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function employeeHeadProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_head_profile_id');
    }

    public function digitalCertificate(): BelongsTo
    {
        return $this->belongsTo(DigitalCertificate::class);
    }

    public function digitalDtrSignatureRequestFile(): HasOne
    {
        return $this->hasOne(DigitalDtrSignatureRequestFile::class, 'digital_dtr_signature_request_id', 'id');
    }

    public function digitalDtrSignatureLog(): HasOne
    {
        return $this->hasOne(DigitalDtrSignatureLog::class, 'digital_dtr_signature_request_id', 'id');
    }
}
