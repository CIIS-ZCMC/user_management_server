<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalDtrSignatureRequestFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'digital_dtr_signature_request_id',
        'file_name',
        'file_path',
        'file_extension',
        'file_size',
    ];

    protected $table = 'digital_dtr_signature_request_files';

    public function digitalDtrSignatureRequest(): BelongsTo
    {
        return $this->belongsTo(DigitalDtrSignatureRequest::class, 'digital_dtr_signature_request_id');
    }
}
