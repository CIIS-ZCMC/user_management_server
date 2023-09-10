<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $table = 'employee_profiles';

    public $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'sex',
        'dob',
        'nationality',
        'religion',
        'dialect'
    ];

    public $timestamps = TRUE;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employmentPosition()
    {
        return $this->belongsTo(EmploymentPosition::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function contact()
    {
        return $this->hasOne(Contact::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}
