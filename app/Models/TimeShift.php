<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

use App\Models\Section;

class TimeShift extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'time_shifts';

    protected $primaryKey = 'id';

    protected $fillable = [
        'first_in',
        'first_out',
        'second_in',
        'second_out',
        'total_hours',
        'color'
    ];

    protected $softDelete = true;

    public $timestamps = true;

    public function division()
    {
        return $this->belongsToMany(Division::class, 'section_time_shift')->withPivot('division_id');
    }

    public function department()
    {
        return $this->belongsToMany(Department::class, 'section_time_shift', 'department_id')->withPivot('department_id');
    }

    public function section()
    {
        return $this->belongsToMany(Section::class, 'section_time_shift', 'section_id')->withPivot('section_id');
    }

    public function unit()
    {
        return $this->belongsToMany(Unit::class, 'section_time_shift', 'unit_id')->withPivot('unit_id');
    }
}