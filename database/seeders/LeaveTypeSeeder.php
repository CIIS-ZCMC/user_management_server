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

        $vacation_leave = LeaveType::create([
            'name' => "Vacation Leave",
            'code' => "VL",
            'description' => 'Depends on the leave credit balances',
            'period' => 0,
            'file_date' => 'First 5 days of the month.',
            'month_value' => 12/15,
            'annual_credit' => 15,
            'is_active' => 1,
            'is_special' => 0,
            'is_country' => 0,
            'is_illness' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);


        $sick_leave = LeaveType::create([
            'name' => "Sick Leave",
            'code' => "SL",
            'description' => 'On account of SICKNESS of the EMPLOYEE and IMMEDIATE family members',
            'period' => 0,
            'file_date' => 'First 3 days after recovery.',
            'month_value' => 12/15,
            'annual_credit' => 15,
            'is_active' => 1,
            'is_special' => 0,
            'is_country' => 0,
            'is_illness' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        LeaveTypeRequirement::create([
            'leave_type_id' => $sick_leave->id,
            'leave_requirement_id' => $requiment_two->id
        ]);

        $special_privilege_leave = LeaveType::create([
            'name' => "Special Privilege Leave",
            'code' => "SPL",
            'description' => 'Maybe granted after the Probationary period (6 months continuous service)
            Granted to mark personal milestones and/or attend to filial and domestic responsibilities',
            'period' => 0,
            'file_date' => 'Annual 3 days allocated leave',
            'month_value' => 3,
            'annual_credit' => 3,
            'is_active' => 1,
            'is_special' => 0,
            'is_country' => 0,
            'is_illness' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);


        $force_leave = LeaveType::create([
            'name' => "Forced Leave",
            'code' => "FL",
            'description' => 'Balance of 10 days/more VL',
            'period' => 0,
            'file_date' => 'Employee will receive 5 credits of force leave upon he/she has reach 10 leave credits.',
            'month_value' => 5,
            'annual_credit' => 5,
            'is_active' => 1,
            'is_special' => 0,
            'is_country' => 0,
            'is_illness' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $soloparent_leave = LeaveType::create([
            'name' => "Solo Parent",
            'code' => "SP",
            'description' => 'Granted to a qualified FEMALE public servant in every instance of pregnancy',
            'period' => 7,
            'file_date' => 'Employee will receive 5 credits of force leave upon he/she has reach 10 leave credits.',
            'month_value' => 0,
            'annual_credit' => 7,
            'is_active' => 1,
            'is_special' => 0,
            'is_country' => 0,
            'is_illness' => 0,
            'is_days_recommended' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $maternity_leave = LeaveType::create([
            'name' => "Maternity Leave",
            'code' => "ML",
            'description' => 'Granted to a qualified FEMALE public servant in every instance of pregnancy',
            'period' => 105,
            'file_date' => 'Employee will receive 5 credits of force leave upon he/she has reach 10 leave credits.',
            'month_value' => 0,
            'annual_credit' => 0,
            'is_active' => 1,
            'is_special' => 1,
            'is_country' => 0,
            'is_illness' => 0,
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

    }
}