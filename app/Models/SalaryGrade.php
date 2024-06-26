<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryGrade extends Model
{
    use HasFactory;

    protected $table = 'salary_grades';

    public $fillable = [
        'salary_grade_number',
        'one',
        'two',
        'three',
        'four',
        'five',
        'six',
        'seven',
        'eight',
        'tranch',
        'effective_at',
        'is_active'
    ];

    public $timestamps = TRUE;

    public function designation()
    {
        return $this->hasMany(Designation::class);
    }
}
