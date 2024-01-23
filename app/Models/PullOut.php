<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PullOut extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pull_outs';

    protected $primaryKey = 'id';

    protected $fillable = [
        'employee_profile_id',
        'requested_employee_id',
        'approve_by_employee_id',
        'pull_out_date',
        'approval_date',
        'reason',
        'status',
    ];
    
    protected $softDelete = true;

    public $timestamps = true;

    public function employee()
    {
        return $this->belongsToMany(EmployeeProfile::class, 'pull_out_employee')->withPivot('employee_profile_id');
    }

    public function requestedBy()
    {
        return $this->belongsToMany(EmployeeProfile::class, 'requested_by');
    }

    public function approveBy()
    {
        return $this->belongsToMany(EmployeeProfile::class, 'approve_by');
    }
}
