<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Division;
use App\Models\Department;
use App\Models\Section;
use App\Models\Unit;
use Illuminate\Http\Response;

class ErpDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $divisions = Division::all();
            $departments = Department::all();
            $sections = Section::all();
            $units = Unit::all();
            
            $erp_data = [
                'divisions' => $divisions->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'code' => $item->code,
                        'name' => $item->name,
                        'chief_employee_profile_id' => $item->chief_employee_profile_id,
                        'oic_employee_profile_id' => $item->oic_employee_profile_id
                    ];
                }),
                'departments' => $departments->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'code' => $item->code,
                        'name' => $item->name,
                        'division_id' => $item->division_id,
                        'chief_employee_profile_id' => $item->chief_employee_profile_id,
                        'oic_employee_profile_id' => $item->oic_employee_profile_id
                    ];
                }),
                'sections' => $sections->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'code' => $item->code,
                        'name' => $item->name,
                        'division_id' => $item->division_id,
                        'department_id' => $item->department_id,
                        'supervisor_employee_profile_id' => $item->supervisor_employee_profile_id
                    ];
                }),
                'units' => $units->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'code' => $item->code,
                        'name' => $item->name,
                        'section_id' => $item->section_id,
                        'unit_head_employee_profile_id' => $item->unit_head_employee_profile_id
                    ];
                })
            ];

            return response()->json([
                'data' => $erp_data,
                'message' => 'Assigned areas retrieved successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $throwable) {
            return response()->json([
                'message' => $throwable->getMessage(),
                'code' => $throwable->getCode()
            ], Response::HTTP_BAD_REQUEST);
        }
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
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
