<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\EmployeeLeaveCredit;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\EmployeeLeaveCreditsImport;
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

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,txt',
        ]);

        try {
            Excel::import(new EmployeeLeaveCreditsImport, $request->file('file'));

            return response()->json([
                'message' => 'Employee leave credits imported successfully.'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
