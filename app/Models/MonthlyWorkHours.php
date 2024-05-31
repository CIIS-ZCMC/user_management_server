<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MonthlyWorkHours extends Model
{
    use HasFactory;
    protected $table = 'monthly_work_hours';

    protected $primaryKey = 'id';

    protected $fillable = [
        'employment_type_id',
        'month_year',
        'work_hours',
    ];

    public $timestamps = true;

    public function employmentType()
    {
        return $this->belongsTo(EmploymentType::class);
    }
}
