<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeOvertimeCredit extends Model
{
    use HasFactory;

    public function employeeProfile()
{
    return $this->belongsTo(EmployeeProfile::class);
}
}
