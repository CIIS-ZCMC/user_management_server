<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';

    public $fillable = [
        'street',
        'barangay',
        'city',
        'province',
        'zip_code',
        'country',
        'is_residential'
    ];

    public $timestamps = TRUE;

    public function employee()
    {
        return $this->belongsTo(Address::class);
    }
}
