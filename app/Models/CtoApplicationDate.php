<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CtoApplicationDate extends Model
{
    use HasFactory;
    protected $table = 'cto_application_dates';
    public $fillable = [
        
        'cto_application_id',
        'time_from',
        'time_to',
        'date',
        'purpose'

    ];

    public function ctoApplication(){
        return $this->belongsTo(ctoApplication::class);
    }
}
