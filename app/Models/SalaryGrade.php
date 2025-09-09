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

    public function salaryGradeAmount(int $step)
    {
        return match ($step) {
            1 => $this->one,
            2 => $this->two,
            3 => $this->three,
            4 => $this->four,
            5 => $this->five,
            6 => $this->six,
            7 => $this->seven,
            8 => $this->eight,
            default => null,
        };
    }
}
