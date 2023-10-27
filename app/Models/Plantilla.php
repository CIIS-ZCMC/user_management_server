<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plantilla extends Model
{
    use HasFactory;

    protected $table = 'plantillas';

    public $fillable = [
        'plantilla_no',
        'tranche',
        'category',
        'effective_at',
        'designation_id'
    ];

    public $timestamps = TRUE;

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }
    
    public function assignedAreas()
    {
        return $this->hasMany(AssignedArea::class);
    }
    
    public function assignedAreaTrails()
    {
        return $this->hasMany(AssignedAreaTrail::class);
    }
}
