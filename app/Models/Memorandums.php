<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Memorandums extends Model
{
    use HasFactory;

    protected $table = 'memorandums';

    public $fillable = [
        'title',
        'attachment',
        'effective_at'
    ];

    public $timestamps = true;
}
