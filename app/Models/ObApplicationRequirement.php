<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObApplicationRequirement extends Model
{
    use HasFactory;
    protected $table = 'ob_application_requirements';

    public $fillable = [
        'ob_application_id',
        'name',
        'file_name',
 
    ];
        public function ob_application(){
            return $this->belongsTo(ObApplication::class);
        }
}
