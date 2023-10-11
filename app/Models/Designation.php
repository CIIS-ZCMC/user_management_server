<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;

    protected $table = 'designations';

    public $fillable = [
        'name',
        'code',
        'salary_grade'
    ];

    public $timestamps = TRUE;

    public function plantilla()
    {
        return $this->belongsTo(Plantilla::class);
    }
}
