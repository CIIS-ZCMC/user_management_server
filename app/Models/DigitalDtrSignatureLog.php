<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalDtrSignatureLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'digital_dtr_signature_request_id',
        'employee_profile_id',
        'action',
        'remarks',
        'action_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'action_at' => 'datetime',
    ];

    /**
     * Get the signature request that owns the log
     */
    public function signatureRequest(): BelongsTo
    {
        return $this->belongsTo(DigitalDtrSignatureRequest::class, 'digital_dtr_signature_request_id');
    }

    /**
     * Get the employee who performed the action
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }
}
