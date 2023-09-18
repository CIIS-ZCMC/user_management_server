<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    protected $table = 'stations';

    public $fillable = [
        'uuid',
        'name',
        'code',
        'department_id'
    ];

    public $timestamps = TRUE;

    public function employeeProfiles()
    {
        return $this->hasMany(EmployeeProfile::class, 'station_id');
    }
}
