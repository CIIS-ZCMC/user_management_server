<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    use HasFactory;

    protected $table = 'access_tokens';

    public $fillable = [
        'user_id',
        'public_key',
        'token',
        'token_exp'
    ];

    public $timestamps = TRUE;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
