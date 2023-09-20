<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plantilla extends Model
{
    use HasFactory;

    protected $table = 'plantillas';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    public $fillable = [
        'uuid',
        'plantilla_no',
        'tranche',
        'date',
        'category',
        'job_position_id'
    ];

    public $timestamps = TRUE;

    public function employees()
    {
        return $this->hasMany(EmployeeProfile::class, 'plantilla_id', 'uuid');
    }

    public function jobPosition()
    {
        return $this->belongsTo(JobPosition::class, 'job_position_id', 'uuid');
    }
}
