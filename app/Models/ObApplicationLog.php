<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObApplicationLog extends Model
{
    use HasFactory;
    protected $table = 'ob_application_logs';

    public $fillable = [
        'action_by',
        'ob_application_id',
        'process_name',
        'status',
        'date',
      
    ];
        public function ob_application(){
            return $this->belongsTo(ObApplication::class);
        }
    }
