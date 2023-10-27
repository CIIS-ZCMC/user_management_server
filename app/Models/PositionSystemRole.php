<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionSystemRole extends Model
{
    use HasFactory;

    protected $table = 'position_system_roles';

    public $fillable = [
        'designation_id',
        'system_role_id'
    ];

    public $timestamps = TRUE;

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function systemRoles()
    {
        return $this->belongsTo(SystemRole::class);
    }
}
