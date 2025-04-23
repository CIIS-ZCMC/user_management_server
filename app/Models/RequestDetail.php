<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestDetail extends Model
{
    use HasFactory;

    protected $table = 'request_details';

    public $fillable = [
        'profile_update_request_id',
        'attachment_url',
        'target_data',
        'new_data'
    ];  

    public $timestamps = TRUE;

    public function profileUpdateRequest()
    {
        return $this->belongsTo(ProfileUpdateRequest::class);
    }
}
