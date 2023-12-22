<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialTimeApplication extends Model
{
    use HasFactory;
    protected $table = 'official_time_applications';

    public $fillable = [
        'user_id',
        'date_from',
        'date_to',
        'time_from',
        'time_to',
        'reason',
        'status',
    ];
        public function requirements()
        {  
            return $this->hasMany(OtApplicationRequirement::class);
        }
        public function logs()
        {
            return $this->hasMany(OtApplicationLog::class);
        }
       
    }
