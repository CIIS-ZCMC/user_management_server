<?php

namespace App\Http\Controllers\Reports;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\FailedLoginTrail;
use App\Models\LoginTrail;
use App\Models\EmployeeProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class LoginActivitiesReport extends Controller
{
    private string $CONTROLLER_NAME = 'LoginActivities Reports';

    /**
     * Generate a report of login activities, including successful and failed logins.
     *
     * This method gathers data on user login activities, including both successful and failed logins.
     * The result is a summary of login counts, along with detailed information on individual login attempts.
     * Optional filtering by employee ID, start date, and end date is provided.
     *
     * @param Request $request HTTP request object containing optional filters (employee_id, start_date, end_date).
     * @return JsonResponse Returns a JSON response containing the login activities report.
     */
    public function generateLoginActivitiesReport(Request $request): JsonResponse
    {
        try {
            // Retrieve optional filters from the request
            $employeeId = $request->input('employee_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Query successful login trails with optional filtering
            $loginQuery = LoginTrail::query()
                ->with('employeeProfile')
                ->when($employeeId, function ($query) use ($employeeId) {
                    return $query->where('employee_profile_id', $employeeId);
                })
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('signin_at', [$startDate, $endDate]);
                });

            // Query failed login trails with optional filtering
            $failedLoginQuery = FailedLoginTrail::query()
                ->with('employeeProfile')
                ->when($employeeId, function ($query) use ($employeeId) {
                    return $query->where('employee_profile_id', $employeeId);
                })
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('created_at', [$startDate, $endDate]);
                });

            // Retrieve the results of both queries
            $loginActivities = $loginQuery->get();
            $failedLoginActivities = $failedLoginQuery->get();

            // Generate a summary of the login activities
            $summary = [
                'total_successful_logins' => $loginActivities->count(),
                'total_failed_logins' => $failedLoginActivities->count(),
                'unique_users_logged_in' => $loginActivities->unique('employee_profile_id')->count(),
                'unique_users_failed_logins' => $failedLoginActivities->unique('employee_profile_id')->count(),
                'date_range' => [
                    'start' => $startDate ?? 'No filtered date range',
                    'end' => $endDate ?? 'No filtered date range',
                ],
            ];

            // Structure the detailed response
            $data = [

                'successful_logins' => $loginActivities->map(function ($login) {
                    return [
                        'employee_id' => $login->employeeProfile->employee_id,
                        'signin_at' => $login->signin_at,
                        'ip_address' => $login->ip_address,
                        'device' => $login->device,
                        'platform' => $login->platform,
                        'browser' => $login->browser,
                        'browser_version' => $login->browser_version,
                    ];
                }),
                'failed_logins' => $failedLoginActivities->map(function ($failedLogin) {
                    return [
                        'employee_id' => $failedLogin->employeeProfile->employee_id,
                        'message' => $failedLogin->message,
                        'created_at' => $failedLogin->created_at,
                    ];
                }),
            ];

            // Return the response as a JSON object
            return response()->json([
                'summary' => $summary,
                'data' => $data,
                'message' => 'Login activities report successfully generated.',
            ]);
        } catch (\Throwable $th) {
            // Log the error and return an internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'generateLoginActivitiesReport', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate a report on the login frequency of users.
     *
     * This method retrieves and counts the number of times each user has logged in during a specified period.
     * Optional filters for employee ID and date range can be applied.
     *
     * @param Request $request HTTP request object containing optional filters (employee_id, start_date, end_date).
     * @return JsonResponse Returns a JSON response containing the login frequency report.
     */
    public function generateLoginFrequencyReport(Request $request): JsonResponse
    {
        try {
            // Retrieve optional filters from the request
            $employeeId = $request->input('employee_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Query login trails, grouping by employee_profile_id and counting logins
            $loginFrequencyQuery = LoginTrail::with('employeeProfile.personalInformation')
                ->select('employee_profile_id', \DB::raw('count(*) as login_count'))
                ->when($employeeId, function ($query) use ($employeeId) {
                    return $query->where('employee_profile_id', $employeeId);
                })
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('signin_at', [$startDate, $endDate]);
                })
                ->groupBy('employee_profile_id');

            // Retrieve the login frequency data
            $loginFrequency = $loginFrequencyQuery->get();

            // Enrich the login data with employee details
            $reportData = $loginFrequency->map(function ($entry) {
                $employeeProfile = EmployeeProfile::find($entry->employee_profile_id);
                return [
                    'employee_id' => $employeeProfile->employee_id,
                    'employee_name' => $employeeProfile->personalInformation->employeeName(),
                    'employee_profile_url' => config("app.server_domain") . "/photo/profiles/" . $employeeProfile->profile_url,
                    'login_count' => $entry->login_count,
                ];
            });

            // Generate a summary of the login frequency report
            $summary = [
                'total_logins' => $loginFrequency->sum('login_count'),
                'unique_users_logged_in' => $loginFrequency->count(),
                'date_range' => [
                    'start' => $startDate ?? 'No filtered date range',
                    'end' => $endDate ?? 'No filtered date range',
                ],
            ];

            // Structure the response
            $data = [
                'login_frequency' => $reportData,
            ];

            // Return the response as a JSON object
            return response()->json([
                'summary' => $summary,
                'data' => $data,
                'message' => 'Login frequency report successfully generated.',
            ]);
        } catch (\Throwable $th) {
            // Log the error and return an internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'generateLoginFrequencyReport', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate a report on failed login attempts.
     *
     * This method retrieves data on failed login attempts by users and counts the number of failed attempts per user.
     * Optional filters for employee ID and date range can be applied.
     *
     * @param Request $request HTTP request object containing optional filters (employee_id, start_date, end_date).
     * @return JsonResponse Returns a JSON response containing the failed login attempts report.
     */
    public function generateFailedLoginAttemptsReport(Request $request): JsonResponse
    {
        try {
            // Retrieve optional filters from the request
            $employeeId = $request->input('employee_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Query failed login trails, grouping by employee_profile_id and counting failed attempts
            $failedLoginQuery = FailedLoginTrail::with('employeeProfile.personalInformation')
                ->select('employee_profile_id', \DB::raw('count(*) as failed_attempts'))
                ->when($employeeId, function ($query) use ($employeeId) {
                    return $query->where('employee_profile_id', $employeeId);
                })
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->groupBy('employee_profile_id');

            // Retrieve the failed login data
            $failedLoginAttempts = $failedLoginQuery->get();

            // Structure the failed login data with count per user
            $reportData = $failedLoginAttempts->map(function ($entry) {
                $employeeProfile = $entry->employeeProfile;
                $personalInformation = $employeeProfile->personalInformation;

                return [
                    'employee_id' => $employeeProfile->employee_id,
                    'employee_name' => $personalInformation->employeeName(),
                    'failed_attempts' => $entry->failed_attempts,
                ];
            });

            // Generate a summary of the failed login attempts report
            $summary = [
                'total_failed_logins' => $failedLoginAttempts->sum('failed_attempts'),
                'unique_users_with_failed_logins' => $failedLoginAttempts->count(),
                'date_range' => [
                    'start' => $startDate ?? 'No filtered date range',
                    'end' => $endDate ?? 'No filtered date range',
                ],
            ];

            // Structure the response
            $data = [
                'failed_login_attempts' => $reportData,
            ];

            // Return the response as a JSON object
            return response()->json([
                'summary' => $summary,
                'data' => $data,
                'message' => 'Failed Login Attempts report successfully generated.',
            ]);
        } catch (\Throwable $th) {
            // Log the error and return an internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'generateFailedLoginAttemptsReport', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate a report on the devices and browsers used for login.
     *
     * This method retrieves data on the devices, browsers, and platforms used for logins.
     * The result is a count of how many times each unique combination of device, browser, and platform was used.
     * Optional filters for employee ID and date range can be applied.
     *
     * @param Request $request HTTP request object containing optional filters (employee_id, start_date, end_date).
     * @return JsonResponse Returns a JSON response containing the device and browser login report.
     */
    public function generateDeviceBrowserLoginReport(Request $request): JsonResponse
    {
        try {
            // Retrieve optional filters from the request
            $employeeId = $request->input('employee_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Query login trails, grouping by browser, device, and platform, and counting occurrences
            $loginQuery = LoginTrail::with('employeeProfile.personalInformation')
                ->select('browser', 'browser_version', 'device', 'platform', \DB::raw('count(*) as login_count'))
                ->when($employeeId, function ($query) use ($employeeId) {
                    return $query->where('employee_profile_id', $employeeId);
                })
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('signin_at', [$startDate, $endDate]);
                })
                ->groupBy('browser', 'browser_version', 'device', 'platform');

            // Retrieve the grouped login data
            $loginData = $loginQuery->get();

            // Extract unique browsers, devices, and platforms
            $uniqueBrowsers = $loginData->unique('browser')->pluck('browser');
            $uniqueDevices = $loginData->unique('device')->pluck('device');
            $uniquePlatforms = $loginData->unique('platform')->pluck('platform');

            // Structure the data for each unique browser/device combination
            $reportData = $loginData->map(function ($entry) {
                return [
                    'browser' => $entry->browser,
                    'browser_version' => $entry->browser_version,
                    'device' => $entry->device,
                    'platform' => $entry->platform,
                    'login_count' => $entry->login_count,
                ];
            });

            // Generate a summary of the device/browser login report
            $summary = [
                'total_logins' => $loginData->sum('login_count'),
                'unique_combinations' => $loginData->count(),
                'unique_browsers' => $uniqueBrowsers->count(),
                'unique_devices' => $uniqueDevices->count(),
                'unique_platforms' => $uniquePlatforms->count(),
                'browsers_used' => $uniqueBrowsers->toArray(),
                'devices_used' => $uniqueDevices->toArray(),
                'platforms_used' => $uniquePlatforms->toArray(),
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ];

            // Structure the response
            $data = [
                'device_browser_login_data' => $reportData,
            ];

            // Return the response as a JSON object
            return response()->json([
                'summary' => $summary,
                'data' => $data,
                'message' => 'Device & Browser Login report successfully generated.',
            ]);
        } catch (\Throwable $th) {
            // Log the error and return an internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'generateDeviceBrowserLoginReport', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
