<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploymentPosition extends Model
{
    use HasFactory;

    protected $table = 'employment_positions';

    public $fillable = [
        'name',
        'total_employee'
    ];

    public $timestamps = TRUE;

    public function systemRoles()
    {
        return $this->hasMany(PositionSystemRole::class);
    }
}
