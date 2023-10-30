<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficerInChargeTrail extends Model
{
    use HasFactory;

    protected $table = 'officer_in_charge_trails';

    public $fillable = [
        'employee_profile_id',
        'sector_id',
        'sector_code',
        'attachment_url',
        'started_at',
        'ended_at'
    ];

    public $timestamps = TRUE;

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
