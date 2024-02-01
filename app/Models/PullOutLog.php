<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PullOutLog extends Model
{
    use HasFactory;
    protected $table = 'pull_out_logs';

    protected $primaryKey = 'id';

    protected $fillable = [
        'pull_out_id',
        'action_by',
        'action',
    ];

    public $timestamps = true;
}
