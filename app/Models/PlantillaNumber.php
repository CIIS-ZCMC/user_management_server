<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantillaNumber extends Model
{
    use HasFactory;

    protected $table = 'plantilla_numbers';

    public $fillable = [
        'number',
        'assigned_at',
        'plantilla_id'
    ];

    public $timestamps = TRUE;

    public function plantilla()
    {
        return $this->belongsTo(Plantilla::class);
    }

    public function plantillaAssignedArea()
    {
        return $this->hasOne(PlantillaAssignedArea::class);
    }
}
