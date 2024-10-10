<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateAttachments extends Model
{
    use HasFactory;

    protected $table = 'certificate_attachments';

    protected $fillable = [
        'employee_profile_id',
        'filename',
        'file_path',
        'file_extension',
        'file_size',
        'img_name',
        'img_path',
        'img_extension',
        'img_size',
        'cert_password'
    ];

    public function employeeProfile(): BelongsTo {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
