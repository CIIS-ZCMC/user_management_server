<?php

namespace App\Http\Controllers\migration;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\select;

class MResetAllPass extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $emm = null;
        try {
            // dd(EmployeeProfile::all());
            $employees = EmployeeProfile::all();
            // $employees = EmployeeProfile::offset(450)->limit(100)->get();
            $temp = [];
            $employeeProfiles = EmployeeProfile::leftJoin('assigned_areas as aa', 'employee_profiles.id', '=', 'aa.employee_profile_id')
                ->select('employee_profiles.employee_id', 'aa.*')
                ->where(function ($query) {
                    $query->where('aa.section_id', 1);
                })
                ->get()->pluck('employee_profile_id');
            
                $nursing = EmployeeProfile::leftJoin('personal_informations as pi', 'employee_profiles.personal_information_id', '=', 'pi.id')
                ->select('employee_profiles.*', 'pi.first_name', 'pi.last_name')
                ->where('employee_profiles.created_at', '>=', '2024-07-01')->get();
                // dd($nursing->pluck('id'));
                
                
            $csvData = [];
            
            // Define multiple headers
            $headers = [
                'First Name', // Adjust headers as needed,
                'Last Name',
                'ID',
                'PASSWORD', // Assuming 'middle_name' is a field in 'personal_informations'
                // Add more headers as needed
            ];
            
            // Add headers to CSV data array
            $csvData[] = $headers;

            foreach ($nursing as $employee) {
                $password = Helpers::generatePassword();
                $hashPassword = Hash::make($password . config('app.salt_value'));

                 // Prepare CSV data array
               

                    $rowData = [
                        // Example data for each header, adjust as per your data structure
                        $employee->last_name,
                        $employee->first_name,
                        $employee->employee_id,
                        $password,
                        // Add more data fields corresponding to each header
                    ];
                    $csvData[] = $rowData;

                

                // Return response to download the CSV file

                $temp[] = ['id' => $employee->employee_id, 'pass' => $password];
                // if (in_array($employee->id, [2374])) {
                    $employee->authorization_pin = null;
                    $employee->password_encrypted = Crypt::encryptString($hashPassword);
                    $employee->save();
                    $employee_profile = EmployeeProfile::find($employee->id);
                    // $default_password = Helpers::generatePassword();
                    $data = [
                        'employeeID' => $employee_profile->employee_id,
                        'Password' => $password,
                        "Link" => config('app.client_domain')
                    ];
                    if ($employee_profile->personalinformation->contact == null) {
                        continue;
                    }
                    $email = $employee_profile->personalinformation->contact->email_address;
                    if ($email == 'd@gmail.com') {
                        continue;
                    }

                    $name = $employee_profile->personalInformation->name();


                    // SendEmailJob::dispatch('new_account', $email, $name, $data);
                    $temp[] = ['sent' => $email];
                    \Log::info('RESET', [
                        'employeeID' => $employee->employee_id,
                        'password' => $password,
                    ]);
                // }
            }
            // Generate a unique filename for the CSV file
            $fileName = 'nursing_data_' . date('YmdHis') . '.csv';

            // Save CSV data to a temporary file path
            $filePath = storage_path('app/' . $fileName); // Adjust path as needed

            $file = fopen($filePath, 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
            \Log::info('P-Reset&sent Successfully', ["message" => '-------------------------------------------------------------------------------------------']);
            dd($temp);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
