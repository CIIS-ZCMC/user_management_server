<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
            return response()->json(['message' => 'UnAuthorized.'], 401);
        }

        $cookie = json_decode($cookieValue);

        $hasAccessToken = AccessToken::where('session_token', $cookie -> token)->first();

        if(!$hasAccessToken)
        {
            return response() -> json(['message' => 'UnAuthorized.'], 401);
        }

        $tokenExpTime = Carbon::parse($hasAccessToken->token_exp);
        $currentTime = Carbon::now();
        
        $isTokenExpired = $tokenExpTime->isPast();

        if ($isTokenExpired) {
            return response()->json(['error' => 'Access token has expired'], 401);
        }

        $user = $hasAccessToken -> user;
        
        $request->merge(['user' => $user]);
        
        $allowedRoles = Collection::make($access);
        $isNotAuthorize = !$allowedRoles->contains($user->role_id);

        if($isNotAuthorize)
        {
            return abort(403, 'Unauthorized');
        }

        $hasAccessToken -> updateTokenExp();

        return $next($request);
    }
}