<?php

namespace App\Http\Controllers\migration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Designation;
use Carbon\Carbon;
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
                $employee_profile = Designation::create([
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
}
