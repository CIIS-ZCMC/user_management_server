<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialBusinessLog extends Model
{
    use HasFactory;

    protected $table = 'official_business_application_logs';

    protected $primaryKey = 'id';

    protected $fillable = [
        'ob_application_id',
        'action_by',
        'action',
    ];

    public $timestamps = TRUE;
}
