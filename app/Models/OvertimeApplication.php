<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeApplication extends Model
{
    
    use HasFactory;
    protected $table = 'overtime_applications';

    public $fillable = [
        'user_id',
        'overtime_application_id',
        'reference_number',
        'status',
        'purpose',
        'date'
      
    ];
    public function activities()
    {  
        return $this->hasMany(OvtApplicationActivity::class);
    }
}
