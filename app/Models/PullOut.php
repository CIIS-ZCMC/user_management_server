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
        'requesting_officer',
        'approving_officer',
        'pull_out_date',
        'approval_date',
        'status',
        'reason',
    ];
    
    protected $softDelete = true;

    public $timestamps = true;

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(EmployeeProfile::class, 'requesting_officer');
    }

    public function approveBy()
    {
        return $this->belongsTo(EmployeeProfile::class, 'approving_officer');
    }
}
