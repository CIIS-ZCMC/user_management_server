<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'title'
    ];  

     public function logs(){
        return $this->hasMany(Attendance_Information::class,"attendances_id","id");
    }
}
