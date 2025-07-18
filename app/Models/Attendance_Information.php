<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance_Information extends Model
{   
    use HasFactory;

    protected $table = "attendance__information";


    protected $fillable = [
        'biometric_id',
        'name',
        'area',
        'areacode',
        'sector',
        'first_entry',
        'last_entry',
        'attendances_id'
    ];
}
