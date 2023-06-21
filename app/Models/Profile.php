<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        "first_name",
        "middle_name",
        "last_name",
        "extension_name",
        "dob",
        "sex",
        "contact",
        "image_url",
        "created_at",
        "updated_at",
        "deleted"
    ];

    public function user()
    {
        return $this -> belongsTo(User::class);
    }
}
