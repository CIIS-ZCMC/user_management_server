<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plantilla extends Model
{
    use HasFactory;

    protected $table = 'plantillas';

    public $fillable = [
        'plantilla_no',
        'tranche',
        'date',
        'category',
        'job_position_id'
    ];

    public $timestamps = TRUE;

    public function employees()
    {
        return $this->hasMany(EmployeeProfile::class, 'plantilla_id');
    }

    public function jobPosition()
    {
        return $this->hasMany(JobPosition::class, 'uuid');
    }
}
