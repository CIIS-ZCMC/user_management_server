<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recompute extends Model
{
    use HasFactory;

    protected $table = 'recompute';

    protected $primaryKey = 'id';

    public $fillable = [
        'biometric_id',
        'month_of',
        'datecomputed',
    ];

    public $timestamps = false;
}
