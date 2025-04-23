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
                return response()->json(["data" => "/", 'message' => 'un-authorized'], Response::HTTP_UNAUTHORIZED);
            }

            $tokenExpTime = Carbon::parse($hasAccessToken->token_exp);

            $isTokenExpired = $tokenExpTime->isPast();

            if ($isTokenExpired) {
                return response()->json(['error' => 'Access token has expired'], Response::HTTP_UNAUTHORIZED);
            }

            $my_token = AccessToken::where('token', $decryptedToken)->first();
            $my_token->update(['token_exp' => Carbon::now()->addHour()]);

            $user = $hasAccessToken->employeeProfile;

            // Check if the token will expire in 5 minutes
            $fiveMinutesFromNow = Carbon::now()->addMinutes(5);
            $shouldExtendExpiration = $tokenExpTime->lessThanOrEqualTo($fiveMinutesFromNow);

            if($shouldExtendExpiration){
                $hasAccessToken->update(['token_expiration' => Carbon::now()->addMinutes(30)]);
            }

            $request->merge(['user' => $user]);

            /**
             * @next Process the next request, which will validate authorization, 
             * then to the controller if the user is authorized. The response of the controller 
             * will be received by the authorization middleware, then it will return the response
             * where this middleware will receive it and store in variable $response.
             */
            $response = $next($request);

            /**
             *  Extend expiration only when the cookie will about to expire in 5 mins 
             * To prevent unnecessary process
             */
            if ($shouldExtendExpiration) {
                return $response->cookie(
                    config('app.cookie_name'), // The cookie name
                    $cookieValue, // Encrypted token as the value
                    30, // Extend for another 30 minutes
                    '/', // Path
                    config('app.session_domain'), // Domain from config
                    false // HTTPS secure flag
                );
            }

            return $response;
        } catch (\Throwable $th) {
            Helpers::errorLog("Authentication Validation", 'validateSession', $th->getMessage());
            return response()->json(['message' => "Un able to process your request.", "error" => $th->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}