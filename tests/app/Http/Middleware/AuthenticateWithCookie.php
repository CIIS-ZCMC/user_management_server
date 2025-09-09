<?php

namespace App\Http\Middleware;

use App\Helpers\Helpers;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

use Carbon\Carbon;

use App\Models\AccessToken;
use Illuminate\Support\Facades\Cache;


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
        try {
            $cookieValue = $request->cookie(config("app.cookie_name"));

            if (is_array($cookieValue)) {
                $cookieValue = $cookieValue[config("app.cookie_name")];
            }

            if (!$cookieValue) {
                return response()->json(["data" => "/", 'message' => 'un-authorized'], Response::HTTP_UNAUTHORIZED)->cookie(config("app.cookie_name"), '', -1);
            }

            $encryptedToken = json_decode($cookieValue);
            $decryptedToken = Helpers::hashKey($encryptedToken);

            $hasAccessToken = AccessToken::where('token', $decryptedToken)->first();

            if (!$hasAccessToken) {
                return response()->json(['message' => 'Un-Authorized.'], Response::HTTP_UNAUTHORIZED);
            }

            $tokenExpTime = Carbon::parse($hasAccessToken->token_exp);

            $isTokenExpired = $tokenExpTime->isPast();

            if ($isTokenExpired) {
                return response()->json(['error' => 'Access token has expired'], Response::HTTP_UNAUTHORIZED);
            }

            $my_token = AccessToken::where('token', $decryptedToken)->first();
            $my_token->update(['token_exp' => Carbon::now()->addHour()]);

            $user = $hasAccessToken->employeeProfile;

            $request->merge(['user' => $user]);

            return $next($request);
        } catch (\Throwable $th) {
            Helpers::errorLog("Authentication Validation", 'validateSession', $th->getMessage());
            return response()->json(['message' => "Un able to process your request.", "error" => $th->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
