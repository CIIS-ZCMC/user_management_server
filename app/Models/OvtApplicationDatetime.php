<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvtApplicationDatetime extends Model
{
    use HasFactory;
    protected $table = 'ovt_application_datetimes';

    public $fillable = [
        'overtime_application_id',
        'activity_name',
        'quantity',
        'man_hour',
        'period_covered'
      
    ];
    public function employees()
    {  
        return $this->hasMany(OvtApplicationEmployee::class);
    }
    public function activity()
    {
        return $this->belongsTo(OvtApplicationActivity::class);
    }
}
