<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    public $fillable = [
        'uuid',
        'name',
        'code',
        'division_id'
    ];

    public $timestamps = TRUE;

    public function employeeProfile()
    {
        return $this->hasMany(EmployeeProfile::class, 'department_id', 'uuid');
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id', 'uuid');
    }
}
