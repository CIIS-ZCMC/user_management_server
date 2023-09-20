<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $table = 'divisions';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    public $fillable = [
        'uuid',
        'code',
        'name'
    ];

    public $timestamps = TRUE;

    public function departments()
    {
        return $this->hasMany(Department::class, 'uuid', 'division_id');
    }
}
