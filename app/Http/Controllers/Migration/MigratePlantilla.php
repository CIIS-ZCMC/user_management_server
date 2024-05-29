<?php

namespace App\Http\Controllers\migration;

use App\Http\Controllers\Controller;
use App\Models\AssignArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Designation;
use App\Models\EmployeeProfile;
use App\Models\Plantilla;
use App\Models\PlantillaNumber;
use Carbon\Carbon;
use League\Csv\Reader;
use App\Models\SalaryGrade;


class MigratePlantilla extends Controller
{
    //
    public function import()
    {
        try {

            // For migrating the personal information
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Truncate the table
            DB::table('designations')->truncate();

            // Re-enable foreign key checks

            // DB::table('employee_profiles')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::beginTransaction();
            // Path to the CSV file

            $BlizDesigations = DB::connection('sqlsrv')->Select(
                "SELECT [jobpositionid]
                ,[code]
                ,[name]
                ,[grouptype]
                ,[salaryGrade]
                FROM [jobposition]
                "
            );

            foreach ($BlizDesigations as $row) {
                Designation::create([
                    'id' => $row->jobpositionid,
                    'name' => $row->name,
                    'code' => $row->code,
                    'probation' => 6,
                    'effective_at' => Carbon::now(),
                    'salary_grade_id' => SalaryGrade::find($row->salaryGrade == 0 ? 1 : $row->salaryGrade)->id,
                    'position_type' => 'Staff',
                ]);
            }
            DB::commit();
            return response()->json('plantilla migrate successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }

    public function migratePlantillas()
    {
        try {
            // For migrating the personal information
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Truncate the table
            DB::table('plantillas')->truncate();
            DB::table('plantilla_numbers')->truncate();

            // Re-enable foreign key checks

            // DB::table('employee_profiles')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::beginTransaction();
            // Path to the CSV file

            $designations = Designation::all();
            //===> get the designation and create its plantillas

            foreach ($designations as $desig) {
                Plantilla::create([
                    'slot' => 10,
                    'total_used_plantilla_no' => 0,
                    'effective_at' => Carbon::now(),
                    'designation_id' => $desig->id
                ]);
            }

            //===> get all employee, findout what are they designations
            $filePath = storage_path('../app/json_data/EMPLOYEE.csv');



            // Create a CSV reader
            $reader = Reader::createFromPath($filePath, 'r');
            $reader->setHeaderOffset(0); // Assumes first row is header

            // Read the CSV data
            $csvData = $reader->getRecords();


            /////FOR 

            $filePath2 = storage_path('../app/json_data/areaassig.csv');
            // Create a CSV reader
            $reader2 = Reader::createFromPath($filePath2, 'r');
            $reader2->setHeaderOffset(0); // Assumes first row is header
            // Read the CSV data
            $forAssigning = $reader2->getRecords();
            /////
            $over = [];
            $employeeThatMultipleAssignArea = [];
            foreach ($csvData as $index => $row) {
                ////===> findout if the employee is Job order then if not continue this step.
                $data = json_decode(json_encode($row));
                if (!$data->isJO) {
                    // dd($data);
                    $desig = strtolower(str_replace(' ', '', $data->POSITON));

                    $designatid = DB::table('designations')
                        ->select('*', DB::raw("REPLACE(LOWER(name), ' ', '') AS label"))
                        ->where(DB::raw("REPLACE(LOWER(name), ' ', '')"), '=', "$desig")
                        ->get();
                    if (count($designatid) > 1 || count($designatid) < 1) {
                        dd(['no designationid found, or duplicate designation found' => $desig]);
                    }
                    // dd($designatid[0]->id);
                    //===> get the designation_id of that employee and find plantilla that you generated in step1,
                    $PlantillaId = Plantilla::where('designation_id', '=', $designatid[0]->id)->get();
                    if (count($PlantillaId) > 1 || count($PlantillaId) < 1) {
                        dd($PlantillaId);
                    }
                    $employeeId = EmployeeProfile::where('employee_id', '=', $data->id)->get();
                    if (count($employeeId) > 1 || count($employeeId) < 1) {
                        foreach ($employeeId as $temp) {
                            $over[] = $temp->employee_id;
                        }
                    }

                    PlantillaNumber::create(
                        [
                            'number' => 'UMIS-BETA-' . str_pad($index, 4, '0', STR_PAD_LEFT),
                            'is_vacant' => 0,
                            'assigned_at' => Carbon::now(),
                            'is_dissolve' => 0,
                            'plantilla_id' => $PlantillaId[0]->id,
                            'employee_profile_id' => $employeeId[0]->id,
                        ]
                    );
                    //===> generate its unique plantilla number and record in planilla_number table
                } else {
                    $employeeThatMultipleAssignArea[] = ['id' => $data->id, 'designation' => $data->POSITON];
                }
            }
            // foreach ($employeeThatMultipleAssignArea as $data) {
            //     //find the assign
            //     $assignArea = AssignArea::where('employee_profile_id', $data['id'])->first();
            //     if ($assignArea) {
            //         // If the assigned area exists, update section, department, and division
            //         if ($assignArea->division_id == null) {
            //             $assignArea->division_id = $division == '' ? null : $division;
            //         }
            //         if ($assignArea->department_id == null) {
            //             $assignArea->department_id = $department == '' ? null : $department;
            //         }
            //         if ($assignArea->section_id == null) {
            //             $assignArea->section_id = $section == '' ? null : $section;
            //         }

            //         $assignArea->save();
            //     } else {
            //         // If the assigned area doesn't exist, create a new one
            //         AssignArea::create([
            //             'salary_grade_step' => 1,
            //             'employee_profile_id' => $EmployeeProfile[0]->id,
            //             'division_id' => $division == '' ? null : $division,
            //             'department_id' => $department == '' ? null : $department,
            //             'section_id' => $section == '' ? null : $section,
            //             'unit_id' => null,
            //             'designation_id' => $plantillaDesignation,
            //             'plantilla_id' => $plantillaId,
            //             'plantilla_number_id' => $plantillaNumberId[0]->id,
            //             'effective_at' => Carbon::create(2025, 1, 1)
            //         ]);
            //     }
            // }
            DB::commit();
            return response()->json('BETA: generated plantilla associated with designation');
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }
}
