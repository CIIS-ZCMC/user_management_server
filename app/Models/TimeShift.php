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
    ];

    protected $softDelete = true;

    public $timestamps = true;

    public function is24HourDuty(): bool
    {
        return $this->total_hours === 24;
    }
}