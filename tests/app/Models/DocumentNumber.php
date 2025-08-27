<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentNumber extends Model
{
    use HasFactory;

    protected $casts = [
        'is_abroad' => 'boolean',
    ];
    public $fillable = [
        'division_id',
        'document_no',
        'revision_no',
        'document_title',
        'is_abroad',
        'effective_date',
    ];

}
