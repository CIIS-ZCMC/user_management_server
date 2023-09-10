<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\EmploymentPosition;

class EmploymentPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmploymentPosition::create([
            'name' => 'System Analyst I',
            'abbreviation' => 'SA1',
            'salary_grade' => 19,
            'salary' => 51357
        ]);

        EmploymentPosition::create([
            'name' => 'Computer Programmer III',
            'abbreviation' => 'CP3',
            'salary_grade' => 18,
            'salary' => 46725
        ]);

        EmploymentPosition::create([
            'name' => 'Computer Programmer II',
            'abbreviation' => 'CP2',
            'salary_grade' => 15,
            'salary' => 36619
        ]);

        EmploymentPosition::create([
            'name' => 'Computer Programmer I',
            'abbreviation' => 'CP1',
            'salary_grade' => 11,
            'salary' => 27000
        ]);
    }
}
