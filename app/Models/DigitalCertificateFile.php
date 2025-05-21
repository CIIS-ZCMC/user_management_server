<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class DigitalCertificateFile extends Model
{
    use HasFactory;

    protected $table = "digital_certificate_files";

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

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    // Encrypt the certificate password before storing
    public function setCertPasswordAttribute($value)
    {
        $this->attributes['cert_password'] = Crypt::encryptString($value);
    }

    // Decrypt the password when retrieving
    public function getCertPasswordAttribute($value)
    {
        return Crypt::decryptString($value);
    }
}
