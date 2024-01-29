<?php

namespace App\Http\Controllers\LeaveAndOvertime;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class EmployeeApplicationController extends Controller
{
    public function hrmoIndex(Request $request) {
        $employee_profile   = $request->user;
        $employee_area      = $employee_profile->assignedArea->findDetails();

        $data = EmployeeProfile::with(['officialBusiness','officialTime'])->get();
        
        if ($employee_area->sector['Section'] === 'HRMO') {
            return response()->json([
                'data' => $data,
                'message' => 'Retrieved all offical business application'
            ], Response::HTTP_OK);
        }
    }
}
