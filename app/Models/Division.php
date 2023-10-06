<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $table = 'divisions';

    public $fillable = [
        'code',
        'name'
    ];

    public $timestamps = TRUE;

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
