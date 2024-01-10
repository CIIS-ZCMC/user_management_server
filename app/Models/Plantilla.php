<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plantilla extends Model
{
    use HasFactory;

    protected $table = 'plantillas';

    public $fillable = [
        'slot',
        'total_used_plantilla_no',
        'effective_at',
        'designation_id'
    ];

    public $timestamps = TRUE;

    public function plantillaNumbers()
    {
        return $this->hasMany(PlantillaNumber::class);
    }
    
    public function requirement()
    {
        return $this->hasOne(PlantillaRequirement::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }
    
    public function assignedAreas()
    {
        return $this->hasMany(AssignArea::class);
    }
    
    public function assignedAreaTrails()
    {
        return $this->hasMany(AssignAreaTrail::class);
    }
}
