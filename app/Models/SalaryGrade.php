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
        'step',
        'amount',
        'effective_at'
    ];

    public $timestamps = TRUE;

    public function designation()
    {
        return $this->hasMany(Designation::class);
    }
}
