<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateLogs extends Model
{
    use HasFactory;

    protected $table = 'certificate_logs';
    protected $fillable = [
        'certificate_attachment_id',
        'employee_profile_id',
        'action',
        'description'
    ];

    public function employeeProfile(): BelongsTo {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function certificateAttachment(): BelongsTo {
        return $this->belongsTo(CertificateAttachments::class);
    }
}
