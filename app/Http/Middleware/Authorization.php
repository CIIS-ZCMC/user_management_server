<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

use App\Models\JobPosition;
use App\Models\System;
use App\Models\SystemRole;
use App\Models\PositionSystemRole;

class Authorization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $routePermission): Response
    {   
        $user = $request->user;
    
        $jobPosition = JobPosition::where('uuid', $user->job_position_id)->first();

        list($action, $module) = explode(' ', $routePermission);

        $system = System::where('code', env('SYSTEM_ABBREVIATION'))->first();
        $systemRoles = $system->systemRoles;
        $hasPermission = null;

        foreach ($systemRoles as $systemRole) {
            $positionSystemRole = PositionSystemRole::where('system_role_id', $systemRole->uuid)->where('job_position_id', $user->job_position_id)->first();
            if(!$positionSystemRole){
                continue;
            }
            $hasPermission = $systemRole->hasPermission($routePermission);
        }
        
        if(!$hasPermission){
            return response()->json(['message'=>'Un-Authorized.'], 401);
        }
        
        return $next($request);
    }
}
