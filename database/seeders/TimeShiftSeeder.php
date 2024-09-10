<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Helpers\Helpers;

use App\Models\TimeShift;

class TimeShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shift_1 = TimeShift::firstOrCreate(['first_in' => '08:00:00', 'first_out' => '12:00:00', 'second_in' => '13:00:00', 'second_out' => '17:00:00', 'total_hours' => 8, 'color' => '#73A9AD']);
        $shift_2 = TimeShift::firstOrCreate(['first_in' => '06:00:00', 'first_out' => '14:00:00', 'total_hours' => 8, 'color' => '#EC7063']);
        $shift_3 = TimeShift::firstOrCreate(['first_in' => '14:00:00', 'first_out' => '22:00:00', 'total_hours' => 8, 'color' => '#7DCEA0']);
        $shift_4 = TimeShift::firstOrCreate(['first_in' => '22:00:00', 'first_out' => '06:00:00', 'total_hours' => 8, 'color' => '#85C1E9']);
        $shift_5 = TimeShift::firstOrCreate(['first_in' => '07:00:00', 'first_out' => '16:00:00', 'total_hours' => 9, 'color' => '#F7DC6F']);
        $shift_6 = TimeShift::firstOrCreate(['first_in' => '08:00:00', 'first_out' => '08:00:00', 'total_hours' => 24, 'color' => '#C39BD3']);
        $shift_7 = TimeShift::firstOrCreate(['first_in' => '08:00:00', 'first_out' => '12:00:00', 'total_hours' => 4, 'color' => '#FFB1B1']);
        $shift_8 = TimeShift::firstOrCreate(['first_in' => '13:00:00', 'first_out' => '17:00:00', 'total_hours' => 4, 'color' => '#FA7070']);
    }
}
