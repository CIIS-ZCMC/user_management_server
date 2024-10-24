<?php

namespace App\Http\Controllers\PRMonitoring;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeProfileResource;
use App\Models\EmployeeProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PRMonitoringController extends Controller
{
    public function getEmployeeProfileInfo(Request $request)
    {
        $employee_profile_id = $request->employee_profile_id;

        // Retrieve the employee profile with related personalInformation and assignedArea
        $employee_profile = EmployeeProfile::with(['personalInformation', 'assignedArea'])
            ->where('id', $employee_profile_id)
            ->first(); // Using first() instead of get() since you expect one record

        // Check if the employee profile was found
        if (!$employee_profile) {
            return response()->json([
                'message' => 'Employee profile not found.'
            ], 404);
        }

        $data = [];

        // Access the related personalInformation and retrieve the employee name
        // $employee_name = $employee_profile->personalInformation->employeeName() ?? 'Unknown'; // Assuming employeeName is an attribute
        // $employee_assigned_area = $employee_profile->assignedArea->findDetails() ?? 'No details found';
        return response()->json([
            'data' => $employee_profile,
            'message' => 'Employee profile information retrieved successfully.'
        ]);
    }

    public function getUserAuthorization(Request $request)
    {
        try {
            $employee_profile_id = $request->employee_profile_id;
            $employee_profile = EmployeeProfile::find($employee_profile_id);

            if (!$employee_profile) {
                return response()->json(['message' => 'Unable to find employee profike'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json(
                [
                    'message' => 'Data retrieved successfully.',
                    'authorization_pin' => $employee_profile->authorizatoin_pin
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            return response()->json([
                'mesage' => 'ERROR: ' . $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
