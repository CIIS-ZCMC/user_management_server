<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcements extends Model
{
    use HasFactory;

    protected $table = 'announcements';

    public $fillable = [
        'title',
        'content',
        'attachments',
        'scheduled_at',
        'forsupervisors',
        'posted',
    ];
    
    protected $casts = [
        'attachments' => 'array'
    ];

    public $timestamps = true;
}
