<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Models\AssignArea;
use App\Models\SalaryGrade;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdateSalaryGradeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $effectiveDate;

    /**
     * Create a new job instance.
     * 
     * @param {effectiveDate} date salary grade effective date
     */
    public function __construct($effectiveDate)
    {
        $this->effectiveDate = $effectiveDate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
            DB::beginTransaction();

            /**
             * Retrieve all salary grade that match the latest effective date
             */
            $latest_salary_grades = SalaryGrade::whereDate('effective_at', $this->effectiveDate)->get();

            /**
             * Iterate for every salary grade
             * update all primary key of all match salary_grade_number
             */
            foreach($latest_salary_grades as $salary_grade){
                AssignArea::join('salary_grades as sg', 'sg.id', 'assigned_areas.salary_grade_id')
                    ->where('sg.salary_grade_number', $salary_grade->salary_grade_number)
                    ->update(['assigned_areas.salary_grade_id' => $salary_grade->id]);                
            }
            
            DB::commit();
        }catch(\Exception $e){
            DB::rollBack();
            /**
             * Register Log for failure of transaction
             */
            Helpers::errorLog("SalarGradeController", "UpdateSalaryGradeJob", $e->getMessage());
        }
    }
}
