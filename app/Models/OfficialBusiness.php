<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialBusiness extends Model
{
    use HasFactory;

    protected $table = 'official_business_applications';

    protected $primaryKey = 'id';

    protected $fillable = [
        'employee_profile_id',
        'date_from',
        'date_to',
        'status',
        'purpose',
        'personal_order_file',
        'personal_order_path',
        'personal_order_size',
        'certificate_of_appearance',
        'certificate_of_appearance_path',
        'certificate_of_appearance_size',
        'recommending_officer',
        'approving_officer',
        'remarks'
    ];

    public $timestamps = TRUE;

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function recommendingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'recommending_officer');
    }

    public function approvingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'approving_officer');
    }

    public function officialBusinessLogs()
    {
        return $this->hasMany(OfficialBusinessLog::class);
    }

    public function totalDays()
    {
        $dateFrom = Carbon::parse($this->date_from);
        $dateTo = Carbon::parse($this->date_to);

        // Calculate the difference in days
        $totalDays = $dateTo->diffInDays($dateFrom) + 1;

        return $totalDays;
    }
}
