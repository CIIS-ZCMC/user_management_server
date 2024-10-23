<?php

namespace App\Http\Controllers\PRMonitoring;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PRMonitoringController extends Controller
{
    public function getEmployeeProfileInfo(Request $request): JsonResponse
    {
        $employee_profile_id = $request->employee_profile_id;
        $emp = EmployeeProfile::find($employee_profile_id);
        return response()->json([
            'authorization_pin' => $emp->authorization_pin,
        ]);
    }
}
