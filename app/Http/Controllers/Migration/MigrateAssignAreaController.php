<?php

namespace App\Http\Controllers\migration;

use App\Http\Controllers\Controller;
use App\Models\AssignArea;
use App\Models\EmployeeProfile;
use App\Models\Plantilla;
use App\Models\PlantillaNumber;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class MigrateAssignAreaController extends Controller
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
        try {
            // For migrating the personal information
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Truncate the table
            DB::table('assigned_areas')->truncate();
            // Re-enable foreign key checks

            // DB::table('employee_profiles')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::beginTransaction();


            //==>get employee per area.


            $filePath = storage_path('../app/json_data/areaassig.csv');



            // Create a CSV reader
            $reader = Reader::createFromPath($filePath, 'r');
            $reader->setHeaderOffset(0); // Assumes first row is header

            // Read the CSV data
            $csvData = $reader->getRecords();
            $over = [];
            $count = [];
            foreach ($csvData as $index => $row) {
                $division = $row['division'];
                $department = $row['department'];
                $section = $row['section'];
                $employeeid = $row['id'];
                $plantillaNumberId = null;
                $plantillaId = null;


                //get its  plantilla number id
                $EmployeeProfile = EmployeeProfile::where('employee_id', $employeeid)->get();
                if (count($EmployeeProfile) > 1 || count($EmployeeProfile) < 1) {
                    dd(['no Employee found, or duplicate Employee found' => $employeeid]);
                }

                // dd($EmployeeProfile[0]->id);
                $plantillaNumberId = PlantillaNumber::where("employee_profile_id", $EmployeeProfile[0]->id)->get();
                if (count($plantillaNumberId) < 1) {
                    continue;
                }
                // if (count($plantillaNumberId) > 1) {
                //     // dd(['no PlantillaNumber found, or duplicate PlantillaNumber found' => $employeeid]);
                //     $count[] = $employeeid;
                // }
                //get its plantilla id
                $plantillaId = $plantillaNumberId[0]->plantilla_id;
                $plantillaDesignation = Plantilla::find($plantillaId)->designation_id;
                //if  the employee assign_areas existed, update, first the section department then division.
                //if not create the area assigned.
                $assignArea = AssignArea::where('employee_profile_id', $EmployeeProfile[0]->id)->first();

                if ($assignArea) {
                    // If the assigned area exists, update section, department, and division
                    if ($assignArea->division_id == null) {
                        $assignArea->division_id = $division == '' ? null : $division;
                    }
                    if ($assignArea->department_id == null) {
                        $assignArea->department_id = $department == '' ? null : $department;
                    }
                    if ($assignArea->section_id == null) {
                        $assignArea->section_id = $section == '' ? null : $section;
                    }

                    $assignArea->save();
                } else {
                    // If the assigned area doesn't exist, create a new one
                    AssignArea::create([
                        'salary_grade_step' => 1,
                        'employee_profile_id' => $EmployeeProfile[0]->id,
                        'division_id' => $division == '' ? null : $division,
                        'department_id' => $department == '' ? null : $department,
                        'section_id' => $section == '' ? null : $section,
                        'unit_id' => null,
                        'designation_id' => $plantillaDesignation,
                        'plantilla_id' => $plantillaId,
                        'plantilla_number_id' => $plantillaNumberId[0]->id,
                        'effective_at' => Carbon::create(2025, 1, 1)
                    ]);
                }

                // dd($row['section'] === null || $row['section'] === "");
            }

            DB::commit();
            return response()->json('Employee successfully assigned');
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return response()->json([
                'message' => $th->getMessage()
            ]);
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
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
