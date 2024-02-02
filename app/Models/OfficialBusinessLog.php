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
        'official_business_id',
        'action_by',
        'action',
    ];

    public $timestamps = TRUE;

    public function officialBusiness() {
        return $this->belongsTo(OfficialBusiness::class, 'official_business_id');
    }

    public function employee() {
        return $this->belongsTo(EmployeeProfile::class, 'action_by');
    }
}
