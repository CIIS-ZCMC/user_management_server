<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class OnCall extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'on_calls';

    protected $primaryKey = 'id';

    protected $fillable = [
        'employee_profile_id',
        'date',
        'remarks',
    ];

    public $timestamps = true;

    public function employee() 
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
