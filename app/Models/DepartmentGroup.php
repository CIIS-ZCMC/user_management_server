<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentGroup extends Model
{
    use HasFactory;

    protected $table = 'department_groups';

    public $fillable = [
        'code',
        'name'
    ];

    public $timestamps = TRUE;

    public function departments()
    {
        return $this->hasMany(Department::class, 'uuid');
    }
}
