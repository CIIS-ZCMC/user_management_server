<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialBusiness extends Model
{
    use HasFactory;

    protected $table = 'official_business_applications';

    protected $primaryKey = 'id';

    protected $fillable = [
        'date_from',
        'date_to',
        'time_from',
        'time_to',
        'status',
        'purpose',
        'personal_order_file',
        'personal_order_path',
        'personal_order_size',
        'certificate_of_appearance',
        'certificate_of_appearance_path',
        'certificate_of_appearance_size',
        'hrmo_officer',
        'recommending_officer',
        'approving_officer',
        'remarks'
    ];

    public $timestamps = TRUE;

    public function employee() {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function hrmoOfficer() {
        return $this->belongsTo(EmployeeProfile::class, 'hrmo_officer');
    }

    public function recommendingOfficer() {
        return $this->belongsTo(EmployeeProfile::class, 'recommending_officer');
    }

    public function approvingOfficer() {
        return $this->belongsTo(EmployeeProfile::class, 'approving_officer');
    }
}
