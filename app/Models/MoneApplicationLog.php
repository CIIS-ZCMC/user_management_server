<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoneApplicationLog extends Model
{
    use HasFactory;
    protected $table = 'mone_application_logs';
    public $fillable = [
        'monetization_application_id',
        'action_by_id',
        'action',
        'status',
        'date',
        
       
    ];
    public function montization_application(){
        return $this->belongsTo(MonetizationApplication::class);

    }
}
