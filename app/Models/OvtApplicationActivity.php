<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvtApplicationActivity extends Model
{
    use HasFactory;
    protected $table = 'ovt_application_activities';

    public $fillable = [
        'overtime_application_id',
        'name',
        'quantity',
        'man_hour',
        'period_covered'

    ];
    public function dates()
    {
        return $this->hasMany(OvtApplicationDatetime::class);
    }
    public function overtime(){
        return $this->belongsTo(OvertimeApplication::class);
    }
}
