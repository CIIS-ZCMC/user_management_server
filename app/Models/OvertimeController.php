<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeController extends Model
{
    use HasFactory;
    protected $table = 'overtime_applications';

    public $fillable = [
        'employee_profile_id',
        'reference_number',
        'status',
        'purpose',
        'overtime_letter_of_request',
        'path',
        'date',
        'time'

    ];
}
