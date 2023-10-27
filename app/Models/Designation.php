<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;

    protected $table = 'designations';

    public $fillable = [
        'name',
        'code',
        'effective_at',
        'salary_grade_id'
    ];

    public $timestamps = TRUE;

    public function salaryGrade()
    {
        return $this->belongsTo(SalaryGrade::class);
    }

    public function plantilla()
    {
        return $this->belongsTo(Plantilla::class);
    }
}
