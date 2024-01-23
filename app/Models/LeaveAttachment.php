<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveAttachment extends Model
{
    use HasFactory;

    protected $table = 'leave_attachments';

    public $fillable = [
        'leave_type_id',
        'file_name',
        'path',
        'size',
    ];

    public $timestamps = true;

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
