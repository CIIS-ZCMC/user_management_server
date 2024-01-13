<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcements extends Model
{
    use HasFactory;

    protected $table = 'anouncements';

    public $fillable = [
        'title',
        'content',
        'attachments'
    ];
    
    protected $casts = [
        'attachments' => 'array'
    ];

    public $timestamps = true;
}
