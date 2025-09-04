<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Division;
use App\Models\Department;
use App\Models\Section;
use App\Models\Unit;
use App\Models\AssignArea;
use App\Models\Designation;
use App\Models\EmployeeProfile;
use App\Http\Resources\ErpUserResource;
use App\Http\Resources\ErpAssignedAreaResource;
use App\Http\Resources\ErpHolidayResource;
use App\Models\Holiday;
use Illuminate\Http\Response;

class ErpDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function areas()
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
                        'head_employee_profile_id' => $item->head_employee_profile_id,
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
                        'supervisor_employee_profile_id' => $item->supervisor_employee_profile_id,
                        'oic_employee_profile_id' => $item->oic_employee_profile_id
                    ];
                }),
                'units' => $units->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'code' => $item->code,
                        'name' => $item->name,
                        'section_id' => $item->section_id,
                        'head_employee_profile_id' => $item->head_employee_profile_id,
                        'oic_employee_profile_id' => $item->oic_employee_profile_id
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

    public function designations()
    {
        try {
            $designations = Designation::all();

            $erp_data = $designations->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'probation' => $item->probation
                ];
            });

            if (!$designations) {
                return response()->json([
                    'message' => 'No designations found.',
                    'code' => Response::HTTP_NOT_FOUND
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => $erp_data,
                'message' => 'Designations retrieved successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $throwable) {
            return response()->json([
                'message' => $throwable->getMessage(),
                'code' => $throwable->getCode()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function users()
    {
        try {
            $users = EmployeeProfile::with([
                'assignedArea',
                'assignedArea.designation',
                'personalInformation'
            ])->get();

            return response()->json([
                'data' => ErpUserResource::collection($users),
                'message' => 'Users retrieved successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $throwable) {
            return response()->json([
                'message' => $throwable->getMessage(),
                'code' => $throwable->getCode()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function assignedAreas()
    {
        try {
            $assignedArea = AssignArea::all();

            return response()->json([
                'data' => ErpAssignedAreaResource::collection($assignedArea),
                'message' => 'Assigned areas retrieved successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $throwable) {
            return response()->json([
                'message' => $throwable->getMessage(),
                'code' => $throwable->getCode()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function holidays()  
    {
        try {
            $holidays = Holiday::all();

            return response()->json([
                'data' => ErpHolidayResource::collection($holidays),
                'message' => 'Holidays retrieved successfully.'
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
