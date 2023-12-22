<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtApplicationRequirement extends Model
{
    use HasFactory;
    protected $table = 'ot_application_requirements';

    public $fillable = [
        'official_time_application_id',
        'name',
        'file_name',
 
    ];
        public function official_time_application(){
            return $this->belongsTo(OfficialTimeApplication::class);
        }
}
