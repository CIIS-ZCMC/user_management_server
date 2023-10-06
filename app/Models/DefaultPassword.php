<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultPassword extends Model
{
    use HasFactory;

    protected $table = 'default_passwords';

    public $fillable = [
        'password',
        'employee_profile_id',
        'status',
        'effective_at',
        'end_at'
    ];

    public $timestamps = TRUE;
}
