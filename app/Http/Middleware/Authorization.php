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

        $systemRole = DB::table('systems as s')
            ->join('system_roles as sr', 'sr.system_id', 's.uuid')
            ->join('position_system_roles as psr', 'psr.system_role_id', 'sr.uuid')
            ->join('system_role_permissions as srp', 'srp.system_role_id', 'sr.uuid')
            ->join('job_positions as jp', 'jp.uuid', 'psr.job_position_id')
            ->where('jp.uuid', $user->job_position_id)
            ->where('s.code', env('SYSTEM_ABBREVIATION'))
            ->where('srp.action', $action)
            ->where('srp.module', $module)
            ->select()
            ->first();

        return response()->json(['message' => $systemRole], 200);
        
        if($rolePermissions === null){
            return response()->json(['message'=>'Un-Authorized.'], 401);
        }

        $position_system_role = PositionSystemRole::find($positionSystemRoleData->id);
        $system_role = SystemRole::find($position_system_role->system_role_id);

        if(!$system_role->hasPermission($routePermission))
        {
            return response()->json(['message' => 'Un-Available'],400);
        }

        return $next($request);
    }
}
