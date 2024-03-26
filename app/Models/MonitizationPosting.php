<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitizationPosting extends Model
{
    use HasFactory;

    protected $table = 'monitization_postings';

    public $fillable = [
        'effective_filing_date',
        'end_filing_date',
        'remarks',
        'created_by'
    ];
    
    public $timestamps = true;

    public function createdBy()
    {
        return $this->belongsTo(EmployeeProfile::class, 'created_by');
    }

    public function candidates()
    {
        return $this->hasMany(MonitizationCandidate::class);
    }
}
