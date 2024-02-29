<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function shift()
    {
        $firstIn = isset($this->first_in) ? Carbon::parse($this->first_in)->format('H:i A') : null;
        $firstOut = isset($this->first_out) ? Carbon::parse($this->first_out)->format('H:i A') : null;
        $SecondIn = isset($this->second_in) ? Carbon::parse($this->second_in)->format('H:i A') : null;
        $SecondOut = isset($this->second_out) ? Carbon::parse($this->second_out)->format('H:i A') : null;
        
        if ($SecondIn !== null) {
            return $firstIn . '-' . $firstOut . '|' . $SecondIn . '-' . $SecondOut;
        }

        return $firstIn . '-' . $firstOut;
    }
}