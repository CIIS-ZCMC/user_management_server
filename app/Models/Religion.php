<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Religion extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function personalInformations()
    {
        return $this->hasMany(PersonalInformation::class);
    }
}
