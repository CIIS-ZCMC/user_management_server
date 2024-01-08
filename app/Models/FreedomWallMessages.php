<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreedomWallMessages extends Model
{
    use HasFactory;

    protected $table = 'freedom_wall_messages';

    public $fillable = [
        'content',
        'employee_profile_id'
    ];

    public $timestamps = true;

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
