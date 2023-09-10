<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

use App\Models\Department;
use App\Models\EmploymentPosition;
use App\Models\User;
use App\Models\EmployeeProfile;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dob = Carbon::create(1995, 8, 13);

        $employee = EmployeeProfile::create([
            'first_name' => "Tristan jay",
            'middle_name' => 'L',
            'last_name' => 'Amit',
            'sex' => 'Not Applicable',
            'dob' => '1995-8-13',
            'nationality' => 'Filipino',
            'religion' => 'Christianity',
            'dialect' => 'chavacano',
            'department_id' => Department::where('abbreviation', 'OMCC')->first()->id,
            'employment_position_id' => EmploymentPosition::where('abbreviation', 'CP3')->first()->id
        ]);

        $password = 'Zcmc_Umis2023@';
        $hashPassword = Hash::make($password.env('SALT_VALUE'));
        Log::channel('custom-info')->info('Hash Password: '.$hashPassword);
        $encryptedPassword = Crypt::encryptString($hashPassword);
        Log::channel('custom-info')->info('encrypted Hash Password: '.$encryptedPassword);

        $user = new User;
        $user -> employee_id = '2022091351';
        $user -> password_encrypted = $encryptedPassword;
        $user -> approved = now();
        $user -> save();

        $employee -> user_id = $user -> id;
        $employee -> save();
    }
}
