<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploymentType extends Model
{
    use HasFactory;

    protected $table = 'employment_types';

    public $fillable = [
        'name'
    ];

    public $timestamps = TRUE;

    public function inActiveEmployees()
    {
        return $this->hasMany(InActiveEmployee::class);
    }
}
