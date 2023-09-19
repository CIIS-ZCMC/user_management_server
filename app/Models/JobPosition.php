<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosition extends Model
{
    use HasFactory;

    protected $table = 'job_positions';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    public $fillable = [
        'uuid',
        'name',
        'code',
        'salary_grade'
    ];

    public $timestamps = TRUE;

    public function plantilla()
    {
        return $this->belongsTo(Plantilla::class, "uuid", 'job_position_id');
    }
}
