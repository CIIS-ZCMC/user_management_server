<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    use HasFactory;

    protected $table = 'access_tokens';

    public $fillable = [
        'uuid',
        'employee_profile_id',
        'public_key',
        'token',
        'token_exp'
    ];

    public $timestamps = TRUE;

    public function employeeProfile()
    {
        return $this->belongsTo(employeeProfile::class, 'employee_profile_id','uuid');
    }
}
