<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\System;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        "status",
        "ip_address",
        "created_at",
        "deleted"
    ];

    public function user()
    {
        return $this -> belongsTo(User::class);
    }

    public function system()
    {
        return $this -> belongsTo(System::class);
    }
}
