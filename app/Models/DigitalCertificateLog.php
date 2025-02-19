<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalCertificateLog extends Model
{
    use HasFactory;

    protected $table = "digital_certificate_logs";
    
    protected $fillable = [
        'digital_certificate_file_id',
        'employee_profile_id',
        'action',
        'description',
        'performed_at'
    ];

    
    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function digitalCertificateFile(): BelongsTo
    {
        return $this->belongsTo(DigitalCertificateFile::class);
    }
}

