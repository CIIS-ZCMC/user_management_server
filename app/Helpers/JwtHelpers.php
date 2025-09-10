<?php 

namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Config;

class JwtHelpers
{
    public static function generateToken()
    {
        $key = env('JWT_SECRET');
        $payload = [
            'iss' => "laravel",
            'iat' => time(),
            'exp' => time() + 3600, // Token expires in 1 hour
        ];

        return JWT::encode($payload, $key, 'HS256');
    }
}
