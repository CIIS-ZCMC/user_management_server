<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devices extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_name',
        'ip_address',
        'com_key',
        'soap_port',
        'udp_port',
        'serial_number',
        'mac_address',
        'is_registration'
    ];
}
