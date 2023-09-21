<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryGrade extends Model
{
    use HasFactory;

    protected $table = 'salary_grades';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    public $fillable = [
        'uuid',
        'salary_grade_number',
        'step',
        'amount',
        'effective_at'
    ];

    public $timestamps = TRUE;

    public function jobPositions()
    {
        return $this->hasMany(JobPosition::class, 'uuid', 'salary_grade_id');
    }
}
