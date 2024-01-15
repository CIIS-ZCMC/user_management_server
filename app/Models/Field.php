<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    use HasFactory;


    protected $table = 'fields';
    protected $casts = [
        'is_name' => 'boolean',
    ];

    public $fillable = [
        'is_name',

    ];

    public function leaveTypes() {
        return $this->belongsToMany(LeaveType::class);
    }

    public function leaveTypeFields()
    {
        return $this->hasMany(LeaveType::class, 'field_id');
    }
}
