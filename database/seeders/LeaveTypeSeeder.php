<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use App\Models\LeaveTypeRequirement;
use App\Models\Requirement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $requiment_one = Requirement::find(1);
        $requiment_two = Requirement::find(2);
        $requiment_three = Requirement::find(3);
        $requiment_four = Requirement::find(4);
        $requiment_five = Requirement::find(5);
        $requiment_six = Requirement::find(6);
        $requiment_seven = Requirement::find(7);
        $requiment_eight = Requirement::find(8);
        $requiment_nine = Requirement::find(9);

        $vacation_leave = LeaveType::create([
            'name' => "Vacation Leave",
            'code' => "VL",
            'description' => 'Depends on the leave credit balances',
            'period' => 0,
            'file_date' => '5 days in advance prior to the effective date of leave',
            'month_value' => 12/15,
            'annual_credit' => 15,
            'is_active' => 1,
            'is_special' => 0,
            'is_country' => 1,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);


        $sick_leave = LeaveType::create([
            'name' => "Sick Leave",
            'code' => "SL",
            'description' => 'On account of SICKNESS of the EMPLOYEE and IMMEDIATE family members',
            'period' => 0,
            'file_date' => 'Immediately upon the employee return',
            'month_value' => 12/15,
            'annual_credit' => 15,
            'is_active' => 1,
            'is_special' => 0,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        LeaveTypeRequirement::create([
            'leave_type_id' => $sick_leave->id,
            'leave_requirement_id' => $requiment_two->id
        ]);
        $sick_leave_exam = LeaveType::create([
            'name' => "Sick Leave (Medical Examination)",
            'code' => "SL",
            'description' => 'To undergo medical examination/ Operation with scheduled date',
            'period' => 0,
            'file_date' => 'Advanced Application',
            'month_value' => 12/15,
            'annual_credit' => 15,
            'is_active' => 1,
            'is_special' => 0,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        LeaveTypeRequirement::create([
            'leave_type_id' => $sick_leave_exam->id,
            'leave_requirement_id' => $requiment_two->id
        ]);

        $special_privilege_leave = LeaveType::create([
            'name' => "Special Privilege Leave",
            'code' => "SPL",
            'description' => 'Maybe granted after the Probationary period (6 months continuous service)
            Granted to mark personal milestones and/or attend to filial and domestic responsibilities',
            'period' => 0,
            'file_date' => 'Personal milestone – One (1) week before. Other reasons under this leave can be filed 1 day after',
            'month_value' => 0,
            'annual_credit' => 3,
            'is_active' => 1,
            'is_special' => 0,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);


        $force_leave = LeaveType::create([
            'name' => "Forced/Mandatory Leave",
            'code' => "FL",
            'description' => 'Balance of 10 days/more VL',
            'period' => 0,
            'file_date' => '5 days in advance prior to the effective date of leave',
            'month_value' => 0,
            'annual_credit' => 5,
            'is_active' => 1,
            'is_special' => 0,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $soloparent_leave = LeaveType::create([
            'name' => "Solo Parent",
            'code' => "SP",
            'description' => 'Any Individual who is left with responsibility of parenthood. Solo parent who has rendered service of at least ONE (1) year. Validated Solo parent ID from DSWDy',
            'period' => 0,
            'file_date' => 'May be filed either before or after the leave',
            'month_value' => 0,
            'annual_credit' => 7,
            'is_active' => 1,
            'is_special' => 0,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $maternity_leave = LeaveType::create([
            'name' => "Maternity Leave",
            'code' => "ML",
            'description' => 'Granted to a qualified FEMALE public servant in every instance of pregnancy',
            'period' => 105,
            'file_date' => '30 days either after or before the delivery, whenever possible',
            'month_value' => 0,
            'annual_credit' => 0,
            'is_active' => 1,
            'is_special' => 1,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // LeaveTypeRequirement::create([
        //     'leave_type_id' => $maternity_leave->id,
        //     'leave_requirement_id' => $requiment_one->id
        // ]);

        LeaveTypeRequirement::create([
            'leave_type_id' => $maternity_leave->id,
            'leave_requirement_id' => $requiment_two->id
        ]);

        LeaveTypeRequirement::create([
            'leave_type_id' => $maternity_leave->id,
            'leave_requirement_id' => $requiment_three->id
        ]);

        
        $allocation_maternity_leave = LeaveType::create([
            'name' => "Allocation of Maternity Leave (Paternity leave)",
            'code' => "AML(PL)",
            'description' => 'Granted to Child’s Father, whether or not the same is Married to the female worker',
            'period' => 7,
            'file_date' => 'Must be availed ONLY within the maternity period of the spouse. May be filed immediately, during or after the childbirth or miscarriage',
            'month_value' => 0,
            'annual_credit' => 0,
            'is_active' => 1,
            'is_special' => 1,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        LeaveTypeRequirement::create([
            'leave_type_id' => $allocation_maternity_leave->id,
            'leave_requirement_id' => $requiment_four->id
        ]);

        $paternity_leave = LeaveType::create([
            'name' => "Paternity leave (Regular Paternity leave)",
            'code' => "PL",
            'description' => 'Granted to MARRIED Male Employees',
            'period' => 7,
            'file_date' => 'May be filed immediately, during or after the childbirth or miscarriage. Must be availed ONLY within the maternity period of the spouse',
            'month_value' => 0,
            'annual_credit' => 0,
            'is_active' => 1,
            'is_special' => 1,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $study_leave = LeaveType::create([
            'name' => "Study leave",
            'code' => "STL",
            'description' => 'Graduated a bachelor’s degree
            Must have completed all the academic requirements for a master’s degree
            Field of study must be relevant to the agency/ to the position
            Must be PERMANENT employee
            Must have no pending Administrative or Criminal charges
            At least TWO(2) years of service with at least Very Satisfactory performance for the last TWO (2) ratings periods immediately preceding the application
            Must not have any current foreign or local scholarship grant
            Must have FULFILLED the service obligation of any previous scholarships and training contract',
            'period' => 182.5,
            'file_date' => 'Should be filed in ADVANCE',
            'month_value' => 0,
            'annual_credit' => 0,
            'is_active' => 1,
            'is_special' => 1,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 1,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        LeaveTypeRequirement::create([
            'leave_type_id' => $study_leave->id,
            'leave_requirement_id' => $requiment_five->id
        ]);
        LeaveTypeRequirement::create([
            'leave_type_id' => $study_leave->id,
            'leave_requirement_id' => $requiment_six->id
        ]);
        
        $adoption_leave = LeaveType::create([
            'name' => "Adoption leave",
            'code' => "AL",
            'description' => 'SIMILAR as of the Maternity and Paternity leave',
            'period' => 60,
            'file_date' => 'SAME as of the Maternity and Paternity leave',
            'month_value' => 0,
            'annual_credit' => 0,
            'is_active' => 1,
            'is_special' => 1,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        LeaveTypeRequirement::create([
            'leave_type_id' => $adoption_leave->id,
            'leave_requirement_id' => $requiment_seven->id
        ]);
       

        $vawc_leave = LeaveType::create([
            'name' => "VAWC leave",
            'code' => "VAWCL",
            'description' => 'For WOMEN who have been a victim of violence',
            'period' => 10,
            'file_date' => 'May be applied for before the actual leave of absence or immediately upon return from such leave.
            May be availed of in a continuous or intermitent manner',
            'month_value' => 0,
            'annual_credit' => 0,
            'is_active' => 1,
            'is_special' => 1,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        LeaveTypeRequirement::create([
            'leave_type_id' => $vawc_leave->id,
            'leave_requirement_id' => $requiment_eight->id
        ]);
       

        $rehab_leave = LeaveType::create([
            'name' => "Rehabilitation leave",
            'code' => "RL",
            'description' => 'All personnel with permanent, temporary, casual or contractual appointments, including those with fixed terms of office',
            'period' => 182.5,
            'file_date' => 'Should be made within ONE (1) week from the time of the accident',
            'month_value' => 0,
            'annual_credit' => 0,
            'is_active' => 1,
            'is_special' => 1,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        LeaveTypeRequirement::create([
            'leave_type_id' => $rehab_leave->id,
            'leave_requirement_id' => $requiment_six->id
        ]);
        LeaveTypeRequirement::create([
            'leave_type_id' => $rehab_leave->id,
            'leave_requirement_id' => $requiment_two->id
        ]);


        $special_leave_women = LeaveType::create([
            'name' => "Special leave for Women",
            'code' => "RL",
            'description' => 'Female public sector employees. Rendered 6 months aggregate services in any or various government agencies for the last twelve(12) months prior to undergoing surgery for gynecological disorders.',
            'period' => 61,
            'file_date' => 'May be applied: 
            •In advance, that is, at least 5 days prior to the scheduled date of the gynecological surgery that will be undergone by the employee.
            •Can be filed IMMEDIATELY upon return during emergency surgical procedures.',
            'month_value' => 0,
            'annual_credit' => 0,
            'is_active' => 1,
            'is_special' => 1,
            'is_country' => 0,
            'is_illness' => 1,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        LeaveTypeRequirement::create([
            'leave_type_id' => $special_leave_women->id,
            'leave_requirement_id' => $requiment_two->id
        ]);
        LeaveTypeRequirement::create([
            'leave_type_id' => $special_leave_women->id,
            'leave_requirement_id' => $requiment_nine->id
        ]);

        
        $special_calamity = LeaveType::create([
            'name' => "Special Calamity/Emergency leave",
            'code' => "SCL",
            'description' => 'May be availed of by the directly affected government employees',
            'period' => 5,
            'file_date' => 'Within 30 days from the first day of calamity declaration',
            'month_value' => 0,
            'annual_credit' => 0,
            'is_active' => 1,
            'is_special' => 1,
            'is_country' => 0,
            'is_illness' => 0,
            'is_study' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        

    }
}
