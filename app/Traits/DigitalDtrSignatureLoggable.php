<?php

namespace App\Traits;

use App\Models\DigitalDtrSignatureLog;
use App\Models\DigitalDtrSignatureRequest;
use Illuminate\Support\Facades\Auth;

trait DigitalDtrSignatureLoggable
{
    /**
     * Log digital DTR signature related actions
     *
     * @param int $digital_signature_request_id
     * @param string $action
     * @param string|null $remarks
     * @return void
     */
    protected function logDtrSignatureAction(
        int $digital_signature_request_id,
        int $employee_profile_id,
        string $action,
        ?string $remarks = null
    ): void {
        DigitalDtrSignatureLog::create([
            'digital_dtr_signature_request_id' => $digital_signature_request_id,
            'employee_profile_id' => $employee_profile_id,
            'action' => $action,
            'remarks' => $remarks,
            'action_at' => now()
        ]);
    }
}
