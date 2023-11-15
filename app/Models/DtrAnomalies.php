<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DtrAnomalies extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'dtr_entry',
        'status',
        'status_desc'
    ];
}
