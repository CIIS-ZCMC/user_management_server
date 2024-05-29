<?php

namespace App\Http\Controllers\Migration;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use League\Csv\Reader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use App\Models\EmployeeProfile;
use App\Models\EmploymentType;

class MigrateController extends Controller
{

    public function import()
    {
        try {

            // $employee_profile = EmployeeProfile::find(471);
            // $default_password = Helpers::generatePassword();
            // $body = [
            //     'employeeID' => $employee_profile->employee_id,
            //     'Password' => $default_password,
            //     "Link" => config('app.client_domain')
            // ];

            // $email = $employee_profile->personalinformation->contact->email_address;
            // $name = $employee_profile->personalInformation->name();

            // $data = [
            //     'Subject' => 'Your Zcmc Portal Account.',
            //     'To_receiver' => $employee_profile->personalinformation->contact->email_address,
            //     'Receiver_Name' => $employee_profile->personalInformation->name(),
            //     'Body' => $body
            // ];

            // SendEmailJob::dispatch('new_account', $email, $name, $data);
            // dd(['sent' => $email]);
            // For migrating the personal information
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Truncate the table
            DB::table('personal_informations')->where('id', '!=', 1)->delete();

            // Re-enable foreign key checks

            DB::table('employee_profiles')->where('id', '!=', 1)->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::beginTransaction();
            // Path to the CSV file
            $filePath = storage_path('../app/json_data/INNOVATIONS.csv');



            // Create a CSV reader
            $reader = Reader::createFromPath($filePath, 'r');
            $reader->setHeaderOffset(0); // Assumes first row is header

            // Read the CSV data
            $csvData = $reader->getRecords();

            foreach ($csvData as $index => $row) {
                $id = $row['employeeid'];
                $yearsOfService = $this->calculateYearsOfService($id);
                $data = $this->getPersonalInformation($id);
                $index += 1;
                // dd($data);

                DB::table('personal_informations')->insert([
                    'id' => $index,
                    'first_name' => $data->firstname,
                    'middle_name' => $data->middlename,
                    'last_name' => $data->lastname,
                    'name_extension' => $data->nameextension,
                    'years_of_service' => $yearsOfService,
                    'name_title' => $data->nametitle,
                    'sex' => $data->Gender,
                    'date_of_birth' => $data->birthdate,
                    'place_of_birth' => $data->birthplace,
                    'civil_status' => $data->civilstatus,
                    'date_of_marriage' => $data->marriagedate,
                    'citizenship' => $data->citizenship || 'Filipino',
                    'country' => "Philippines",
                    'height' => $data->height,
                    'weight' => $data->weight,
                    'blood_type' => $data->BloodType,
                ]);
                // dd(EmploymentType::find(3)->id);

                $password = 'Zcmc_Umis2023@';

                $hashPassword = Hash::make($password . config('app.salt_value'));
                $encryptedPassword = Crypt::encryptString($hashPassword);

                $now = Carbon::now();
                $fortyDaysFromNow = $now->addDays(40);
                $fortyDaysExpiration = $fortyDaysFromNow->toDateTimeString();

                $employee_profile = EmployeeProfile::create([
                    'employee_id' => $id,
                    'date_hired' => Carbon::createFromFormat('Y-m-d', $data->datehire),
                    'password_encrypted' => $encryptedPassword,
                    'password_created_at' => now(),
                    'password_expiration_at' => $fortyDaysExpiration,
                    'biometric_id' => $index,
                    'allow_time_adjustment' => TRUE,
                    'employment_type_id' => EmploymentType::find(3)->id || 3,
                    'personal_information_id' => $index
                ]);

                Log::info('User Migrate Successfully', [
                    'user_detail' => $employee_profile,
                    'user_name' => $employee_profile,
                    'password' => $password,
                ]);
            }
            Log::info('Personal Information migrate successfully.');



            DB::commit();

            return response()->json('success');
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }

    private function calculateYearsOfService($id)
    {
        // Calculate years of service here
        // You can move the SQL query logic for years of service calculation to a separate function
        // and call it from here
        $yos = DB::connection('sqlsrv')->Select(
            "SELECT SubQueryResult.employeeid,
            SUM(FLOOR(DATEDIFF(DAY, SubQueryResult.employmentdate, SubQueryResult.ConvertedDate) / 365.25)) AS yearofservice
            FROM (
                SELECT 
                    emp.employeeid,
                    emp.no,
                    emp.firstname,
                    emp.lastname,
                    empdet.employeeid AS empdet_employeeid,
                    emt.employmentdate,
                    emt.separationdate,
                    emt.separationdateto,
                    CASE 
                        -- If the date is in the format 'Month DD YYYY'
                        WHEN ISDATE(emt.separationdateto) = 1 AND emt.separationdateto NOT LIKE '%/%' THEN
                            CONVERT(DATE, emt.separationdateto, 107)
                        -- If the date is in the format 'MM/DD/YYYY'
                        WHEN ISDATE(emt.separationdateto) = 1 AND emt.separationdateto LIKE '__/__/____' THEN
                            CONVERT(DATE, emt.separationdateto, 101)
                        -- If the date is 'PRESENT'
                        WHEN emt.separationdateto = 'PRESENT' OR emt.separationdateto = 'PRERSENT' THEN
                            GETDATE() -- Returns today's date
                        -- For any other cases, return NULL or handle as needed
                        ELSE
                            NULL
                    END AS ConvertedDate
                FROM [hrblizge].[dbo].[employeedetail] empdet 
                LEFT JOIN employment emt ON empdet.employeedetailid = emt.employeedetailid 
                RIGHT JOIN employee emp ON emp.employeeid = empdet.employeeid
            ) AS SubQueryResult where SubQueryResult.no = '$id' GROUP BY SubQueryResult.employeeid"
        );
        return $yos[0]->yearofservice; // Placeholder value
    }

    private function getPersonalInformation($id)
    {
        // Fetch personal information from the database
        // You can move the SQL query logic for fetching personal information to a separate function
        // and call it from here
        $data = DB::connection('sqlsrv')->SELECT(
            "SELECT
            emp.employeeid,
            emp.no,
            emp.firstname,
            emp.lastname,
            emp.middlename,
            empDet.nametitle,
            emp.nameextension,
            emp.birthdate,
            emp.datehire,
            CONCAT(LOWER(REPLACE(emp.middlename, ' ', '')),LOWER(REPLACE(emp.firstname, ' ', '')),LOWER(REPLACE(REPLACE(emp.nameextension, ' ', ''), '.', ''))) as aa,
            CASE
                WHEN empDet.gender = 1 THEN
                    'Male'
                ELSE 'Female'
            END as Gender,
            empDet.birthplace,
            CASE
                WHEN empDet.civilstatus = 1 or empDet.civilstatus = 0 THEN
                    'Single'
                ELSE 'Married'
            END as civilstatus,
            empDet.marriagedate,
            empDet.height,
            empDet.weight,
            empDet.agencyemployeeno,
            empNat.name as citizenship,
            case
                when empDet.bloodtype = 1 THEN
                    'A+'
                when empDet.bloodtype = 2 THEN
                    'B+'
                when empDet.bloodtype = 3 THEN
                    'AB+'
                when empDet.bloodtype = 4 THEN
                    'O+'
                when empDet.bloodtype = 5 THEN
                    'A-'
                when empDet.bloodtype = 6 THEN
                    'B-'
                when empDet.bloodtype = 7 THEN
                    'AB-'
                when empDet.bloodtype = 8 THEN
                    'O-'
            end as BloodType
            FROM dbo.employee AS emp
            LEFT JOIN dbo.employeedetail AS empDet ON emp.employeeid = empDet.employeeid
            LEFT JOIN dbo.nationality as empNat ON empDet.nationalityid = empNat.nationalityid
            Where emp.no = '$id' "
        );
        return $data[0]; // Placeholder value
    }
}
