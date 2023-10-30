<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileUpdateRequest extends Model
{
    use HasFactory;

    protected $table = 'profile_update_requests';

    public $fillable = [
        'employee_profile_id',
        'approved_by',
        'request_at',
        'approved_at'
    ];

    public $timestamps = TRUE;

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function approveBy()
    {
        return $this->belongsTo(EmployeeProfile::class, 'id', 'approved_by');
    }

    public function requestDetails()
    {
        return $this->hasMany(RequestDetail::class);
    }
}
