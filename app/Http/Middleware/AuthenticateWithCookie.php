<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

use Carbon\Carbon;

use App\Models\AccessToken;

class AuthenticateWithCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$access)
    {
        $datenow = date('Y-m-d H:i:s');

        $cookieValue = $request->cookie(env('COOKIE_NAME'));

        if (!$cookieValue) {
            return response()->json(['message' => 'Invalid request.'], Response::HTTP_UNAUTHORIZED);
        }


        $encryptedToken = json_decode($cookieValue);
        $decryptedToken = openssl_decrypt($encryptedToken->token, env("ENCRYPT_DECRYPT_ALGORITHM"), env("APP_KEY"), 0, substr(md5(env("APP_KEY")), 0, 16));

        $hasAccessToken = AccessToken::where('token', $decryptedToken)->first();

        if(!$hasAccessToken)
        {
            return response() -> json(['message' => 'Invalid request.'], Response::HTTP_UNAUTHORIZED);
        }

        $tokenExpTime = Carbon::parse($hasAccessToken->token_exp);
        
        $isTokenExpired = $tokenExpTime->isPast();

        if ($isTokenExpired) {
            return response()->json(['error' => 'Access token has expired'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $hasAccessToken->employeeProfile;

        $request->merge(['user' => $user]);
 
        return $next($request);
    }
}