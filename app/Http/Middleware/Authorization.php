<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

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
        $employee_profile = $user->employmentProfile;
        $employment_position = $employee_profile->employmentPosition;

        $positionSystemRoleData = DB::table('position_system_roles as psr')
            ->select('psr.id')
            ->join('system_roles as sr', 'sr.id', 'psr.system_roles_id')
            ->join('systems as s', 's.id', 'sr.system_id')
            ->where('psr.employment_position_id', $employment_position->id)
            ->where('s.abbreviation', env('SYSTEM_ABBREVIATION'))
            ->first();

        if($positionSystemRoleData === NULL){
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
