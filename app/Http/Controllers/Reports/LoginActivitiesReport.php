<?php

namespace App\Http\Controllers\Reports;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\FailedLoginTrail;
use App\Models\LoginTrail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LoginActivitiesReport extends Controller
{
    private $CONTROLLER_NAME = 'LoginActivities Reports';

    /**
     * Retrieves the list of failed login attempts with optional filtering.
     *
     * @param array $filters Optional filters for employee profile ID, date range, and error message.
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse List of failed login attempts or error response.
     */
    public function getFailedLoginAttempts(Request $request)
    {
        try {
            $employees = EmployeeProfile::with('failedLoginTrails')->whereNull('deactivated_at');

            return response()->json([
                'count' => $employees->count(),

                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getFailedLoginAttempts', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Retrieves the list of successful logins with optional filtering.
     *
     * @param array $filters Optional filters for employee profile ID, date range, and device.
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse List of successful logins or error response.
     */
    public function getSuccessfulLogins(Request $request)
    {
        try {
            $query = LoginTrail::get();
            return response()->json([
                'count' => $query->count(),
                'data' => $query,
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getSuccessfulLogins', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Retrieves the frequency of logins for employees within an optional date range.
     *
     * @param array $filters Optional filters for employee profile ID and date range.
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse List of login frequencies or error response.
     */
    public function getLoginFrequency(Request $request)
    {
        try {
            $query = LoginTrail::query();
            // Group by employee profile ID and count the number of logins
            $result = $query->selectRaw('employee_profile_id, COUNT(*) as login_count')
                ->groupBy('employee_profile_id')
                ->get();

            return response()->json([
                'count' => $result->count(),
                'data' => $result,
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getLoginFrequency', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Retrieves the ratio of successful to failed login attempts for employees within an optional date range.
     *
     * @param array $filters Optional filters for employee profile ID and date range.
     * @return array|\Illuminate\Http\JsonResponse Array containing failed, successful login counts and their ratio, or error response.
     */
    public function getLoginSuccessFailureRatio(Request $request)
    {
        try {
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getLoginSuccessFailureRatio', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Retrieves analysis of login sources, including IP address, device, platform, and browser, with optional filtering.
     *
     * @param array $filters Optional filters for employee profile ID and date range.
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse List of login sources or error response.
     */
    public function getLoginSourceAnalysis(Request $request)
    {
        try {
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getLoginSourceAnalysis', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Retrieves an audit report for a specific employee profile, including both failed and successful logins.
     *
     * @param int $employeeProfileId The ID of the employee profile.
     * @param array $filters Optional filters for date range.
     * @return array|\Illuminate\Http\JsonResponse Array containing failed and successful logins, or error response.
     */
    public function getAuditReport(Request $request)
    {
        try {
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getAuditReport', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
