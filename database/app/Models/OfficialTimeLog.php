<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialTimeLog extends Model
{
    use HasFactory;
    
    protected $table = 'official_time_application_logs';

    protected $primaryKey = 'id';

    protected $fillable = [
        'official_time_id',
        'action_by',
        'action',
    ];

    public $timestamps = TRUE;

    public function officialTime() {
        return $this->belongsTo(OfficialTime::class, 'official_time_id');
    }

    public function employee() {
        return $this->belongsTo(EmployeeProfile::class, 'action_by');
    }
}
