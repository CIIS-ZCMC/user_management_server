<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantillaRequirement extends Model
{
    use HasFactory;

    protected $table = 'plantilla_requirements';

    public $fillable = [
        'education',
        'training',
        'experience',
        'eligibility',
        'competency',
        'plantilla_id'
    ];

    public $timestamps = TRUE;

    public function plantilla()
    {
        return $this->belongsTo(Plantilla::class);
    }
}
