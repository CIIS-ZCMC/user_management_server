<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionSystemRole extends Model
{
    use HasFactory;

    protected $table = 'position_system_roles';

    public $fillable = [
        'job_position_id',
        'system_role_id'
    ];

    public $timestamps = TRUE;

    public function position()
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function systemRoles()
    {
        return $this->belongsTo(SystemRole::class);
    }

    public function systems()
    {
        return $this->hasManyThrough(System::class, SystemRole::class, 'id', 'id');
    }
}
