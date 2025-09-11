<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\EmployeeLeaveCredit;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\EmployeeLeaveCreditsImport;
use App\Imports\EmployeeOvertimeCreditsImport;
use App\Models\EmployeeOvertimeCredit;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class EmployeeLeaveCreditController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeLeaveCredit $employeeLeaveCredit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeLeaveCredit $employeeLeaveCredit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeLeaveCredit $employeeLeaveCredit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeLeaveCredit $employeeLeaveCredit)
    {
        //
    }

    public function importLeave(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,txt',
        ]);

        try {
            // Run the import
            Excel::import(new EmployeeLeaveCreditsImport, $request->file('file'));

            // Build the same response format as getEmployees
            $leaveCredits = EmployeeLeaveCredit::with(['employeeProfile.personalInformation', 'leaveType'])
                ->whereHas('employeeProfile', function ($query) {
                    $query->whereNotNull('employee_id');
                })
                ->whereHas('employeeProfile', function ($query) {
                    $query->where('employment_type_id', '!=', 5);
                })
                ->get()
                ->groupBy('employee_profile_id');

            $response = [];
            foreach ($leaveCredits as $employeeProfileId => $leaveCreditGroup) {
                $employeeDetails = $leaveCreditGroup->first()->employeeProfile->personalInformation->name();
                $leaveCreditData = [];

                foreach ($leaveCreditGroup as $leaveCredit) {
                    $leaveCreditData[$leaveCredit->leaveType->name] = $leaveCredit->total_leave_credits;
                }

                // Fetch CTO credit
                $ctoCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employeeProfileId)
                    ->value('earned_credit_by_hour');

                $leaveCreditData['CTO'] = $ctoCredit;

                $employeeResponse = [
                    'id'          => $employeeProfileId,
                    'name'        => $employeeDetails,
                    'employee_id' => $leaveCreditGroup->first()->employeeProfile->employee_id,
                ];

                $employeeResponse = array_merge($employeeResponse, $leaveCreditData);
                $response[] = $employeeResponse;
            }

            return ['data' => $response];
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Import failed.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_type' => 'required|in:leave,overtime',
            'file'        => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $importType = $request->input('import_type');
        $file = $request->file('file');

        try {
            $importClass = null;

            switch ($importType) {
                case 'leave':
                    $importClass = new EmployeeLeaveCreditsImport();
                    break;
                case 'overtime':
                    $importClass = new EmployeeOvertimeCreditsImport();
                    break;
            }

            Excel::import($importClass, $file);

            // After import, rebuild employee credits structure
            $leaveCredits = EmployeeLeaveCredit::with(['employeeProfile.personalInformation', 'leaveType'])
                ->whereHas('employeeProfile', function ($query) {
                    $query->whereNotNull('employee_id');
                })
                ->whereHas('employeeProfile', function ($query) {
                    $query->where('employment_type_id', '!=', 5);
                })
                ->get()
                ->groupBy('employee_profile_id');

            $response = [];
            foreach ($leaveCredits as $employeeProfileId => $leaveCreditGroup) {
                $employeeDetails = $leaveCreditGroup->first()->employeeProfile->personalInformation->name();
                $leaveCreditData = [];

                foreach ($leaveCreditGroup as $leaveCredit) {
                    $leaveCreditData[$leaveCredit->leaveType->name] = $leaveCredit->total_leave_credits;
                }

                // Fetch 'CTO' credit
                $ctoCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employeeProfileId)
                    ->value('earned_credit_by_hour');

                $leaveCreditData['CTO'] = $ctoCredit;

                $employeeResponse = [
                    'id'          => $employeeProfileId,
                    'name'        => $employeeDetails,
                    'employee_id' => $leaveCreditGroup->first()->employeeProfile->employee_id,
                ];

                $employeeResponse = array_merge($employeeResponse, $leaveCreditData);
                $response[] = $employeeResponse;
            }

            return response()->json([
                'message' => ucfirst($importType) . ' credits imported successfully',
                'data'    => $response,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error during import: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}
