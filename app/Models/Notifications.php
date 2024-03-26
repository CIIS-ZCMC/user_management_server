<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    public $fillable = [
        'description',
        'module_path',
        'seen',
        'employee_profile_id'
    ];

    public $timestamps = true;

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
