<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvtApplicationEmployee extends Model
{
    use HasFactory;
    protected $table = 'ovt_application_datetimes';

    public $fillable = [
        'overtime_application_id',
        'time_from',
        'time_to',
        'date'
      
    ];
    public function date()
    {
        return $this->belongsTo(OvtApplicationDatetime::class);
    }
}
