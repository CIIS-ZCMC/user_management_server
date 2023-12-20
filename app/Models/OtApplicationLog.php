<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtApplicationLog extends Model
{
    use HasFactory;
    protected $table = 'ot_application_logs';

    public $fillable = [
        'action_by_id',
        'official_time_application_id',
        'process_name',
        'status',
        'date',
      
    ];
        public function official_time_application(){
            return $this->belongsTo(OfficialTimeApplication::class);
        }
}
