<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedcapModules extends Model
{
    use HasFactory;

    protected $table = "redcap_modules";

    public $fillable = [
        'name',
        'code',
        'origin',
        'path'
    ];

    public $timestamp = true;

    public function employeeRedcapModules()
    {
        return $this->hasMany(EmployeeRedcapModules::class);
    }
}
