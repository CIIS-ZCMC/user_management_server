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

        foreach ($permissions['system'] as $key => $value) {
            foreach ($value['roles'] as $key => $value) {
                foreach ($value['modules'] as $key => $value) {
                    if ($value['code'] === $system_module['code']) {
                        if (in_array($action, $value['permissions'])) {
                            $has_rights = true;
                        }
                    }
                }
            }
        }

        if (!$has_rights) {
            return response()->json(['message' => 'Un-Authorized.'], 401);
        }

        $request->merge(['permission' => $routePermission]);

        return $next($request);
    }
}
