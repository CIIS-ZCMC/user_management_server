<?php

namespace App\Traits;

use App\Models\DigitalCertificateLog;

trait DigitalCertificateLoggable
{
    /**
     * Log digital certificate related actions
     *
     * @param int $digitalCertificateFileId
     * @param int $employeeProfileId
     * @param string $action
     * @param string $description
     * @return void
     */
    protected function logCertificateAction(
        int $digitalCertificateFileId,
        int $employeeProfileId,
        string $action,
        string $description
    ): void {
        DigitalCertificateLog::create([
            'digital_certificate_file_id' => $digitalCertificateFileId,
            'employee_profile_id' => $employeeProfileId,
            'action' => $action,
            'description' => $description,
            'performed_at' => now()
        ]);
    }
}
