<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitizationCandidate extends Model
{
    use HasFactory;

    protected $table = 'monitization_candidates';

    public $fillable = [
        'has_filed',
        'employee_profile_id',
        'monitization_posting_id'
    ];  

    public $timestamps = true;

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function posting()
    {
        return $this->belongsTo(MonitizationPosting::class);
    }
}
