<?php

namespace App\Http\Middleware;

use App\Helpers\Helpers;
use App\Models\Apikey;
use App\Models\System;
use Closure;
use Illuminate\Http\Request;
use Response;

class AuthenticateThirdPartySystem
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try{
            $api_key = $request->header('UMIS-Api-Key');

            Helpers::errorLog("Athenticate", "handle", $api_key);
            
            $api_key_exist = System::where("api_key", $api_key)->first();

            if(!$api_key_exist){
                return response()->json(['message' => "Unrecognize third party system."], 404);
            }

            if($api_key_exist->deactivated_at !== null){
                return response()->json(['message' => "Api Key is deactivated contact Telemedicine."], 403);
            }

            $request->merge(['api_key' => $api_key_exist]);

            return $next($request);
        }catch(\Throwable $th){
            Helpers::infoLog("AuthenticateThirdPartySystem", "handle", $th->getMessage());
            return response()->json(['message' => "Something went wrong."], 500);
        }
    }
}
