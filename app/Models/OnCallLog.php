<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnCallLog extends Model
{
    use HasFactory;

    protected $table = 'on_call_logs';

    protected $primaryKey = 'id';

    public $fillable = [
        'on_call_id',
        'action_by',
        'action',
    ];

    public $timestamps = true;
}
