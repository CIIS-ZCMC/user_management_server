<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvtApplicationDatetime extends Model
{
    use HasFactory;
    protected $table = 'ovt_application_datetimes';

    public $fillable = [
        'ovt_application_activity_id',
        'overtime_application_id',
        'time_from',
        'time_to',
        'date'

    ];
    public function employees()
    {
        return $this->hasMany(OvtApplicationEmployee::class);
    }
    public function activities()
    {
        return $this->belongsTo(OvtApplicationActivity::class);
    }
    public function overtimeApplication(){
        return $this->belongsTo(OvertimeApplication::class);
    }
}
