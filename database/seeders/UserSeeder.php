<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\PersonalInformation;
use App\Models\EmployeeProfile;

use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Station;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $personalInformation = PersonalInformation::create([
            'uuid' => Str::uuid(),
            'first_name' => "Tristan jay",
            'middle_name' => 'L',
            'last_name' => 'Amit',
            'sex' => 'Not Applicable',
            'date_of_birth' => '1995-8-13',
            'place_of_birth' => 'Zamboanga City',
            'civil_status' => 'Single',
            'citizenship' => 'Filipino',
            'height' => 172,
            'weight' => 57
        ]);

        $password = 'Zcmc_Umis2023@';
        $hashPassword = Hash::make($password.env('SALT_VALUE'));
        $encryptedPassword = Crypt::encryptString($hashPassword);
        
        $now = Carbon::now();
        $fortyDaysFromNow = $now->addDays(40);
        $fortyDaysExpiration = $fortyDaysFromNow->toDateTimeString();

        $employeProfile = EmployeeProfile::create([
            'uuid' => Str::uuid(),
            'employee_id' => 2023091330,
            'date_hired' => '2023-09-13',
            'job_type' => 'Job Order',
            'department_id' => Department::where('code', 'OMCC')->first()-> uuid,
            'job_position_id' => JobPosition::where('code', 'CP III')->first()-> uuid,
            'station_id' => Station::where('code', 'Supply')->first()-> uuid,
            'personal_information_id' => $personalInformation -> uuid,
            'password_encrypted' => $encryptedPassword,
            'password_created_date' => now(),
            'password_expiration_date' => $fortyDaysExpiration,
            'approved' => now()
        ]);
    }
}
