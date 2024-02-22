<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

use App\Models\Designation;
use App\Models\System;
use App\Models\SystemRole;
use App\Models\SystemModule;
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

        list($module, $action) = explode(' ', $routePermission);

        $system_module = SystemModule::where('code', $module)->first();

        $user = $request->user;

        $employe_designation = $user->findDesignation();

        $permissions = Cache::get($employe_designation->name);

        $has_rights = false;

        if($permissions !== null && count($permissions['system']) !== 0){
            foreach ($permissions['system'] as $key => $value) {
                foreach ($value['modules'] as $key => $data) {
                    if ($data['code'] === $system_module['code']) {
                        if (in_array($action, $data['permissions'])) {
                            $has_rights = true;
                        }
                    }
                    if($has_rights) break;
                }
                if($has_rights) break;
            }
    
            $request->merge(['permission' => $routePermission]);
    
            return $next($request);
        }

        $permissions = Cache::get($user->employee_id);
        
        if(!$has_rights){
            foreach ($permissions['system'] as $key => $value) {
                foreach ($value['modules'] as $key => $data) {
                    if ($data['code'] === $system_module['code']) {
                        if (in_array($action, $data['permissions'])) {
                            $has_rights = true;
                        }
                    }
                    if($has_rights) break;
                }
                if($has_rights) break;
            }
            
            $request->merge(['permission' => $routePermission]);

            return $next($request);
        }
        
        $permissions = Cache::get("COMMON-REG");
        
        if(!$has_rights){
            foreach ($permissions['modules'] as $key => $data) {
                if ($data['code'] === $system_module['code']) {
                    if (in_array($action, $data['permissions'])) {
                        $has_rights = true;
                    }
                }
                if($has_rights) break;
            }
            
            $request->merge(['permission' => $routePermission]);

            return $next($request);
        }
        
        $permissions = Cache::get("COMMON-JO");
        
        if(!$has_rights){
            foreach ($permissions['modules'] as $key => $data) {
                if ($data['code'] === $system_module['code']) {
                    if (in_array($action, $data['permissions'])) {
                        $has_rights = true;
                    }
                }
                if($has_rights) break;
            }
            
            $request->merge(['permission' => $routePermission]);

            return $next($request);
        }

        if (!$has_rights) {
            return response()->json(['message' => 'Un-Authorized.'], 401);
        }

        $request->merge(['permission' => $routePermission]);

        return $next($request);
    }
}
