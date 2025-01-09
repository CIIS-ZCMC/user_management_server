<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class
Holiday extends Model
{
    use HasFactory;

    protected $table = 'holidays';

    protected $primaryKey = 'id';

    protected $fillable = [
        'description',
        'month_day',
        'isspecial',
        'effectiveDate',
    ];


    public $timestamps = true;

}
