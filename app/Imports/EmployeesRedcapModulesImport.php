<?php

namespace App\Imports;

use App\Helpers\Helpers;
use App\Models\EmployeeProfile;
use App\Models\EmployeeRedcapModules;
use App\Models\RedcapModules;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class EmployeesRedcapModulesImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $rows
     * @return void
     */
    public function collection(Collection $rows)
    {
        // Remove null values from each row
        $filteredRows = $rows->map(function ($row) {
            return array_filter($row->toArray(), function ($value) {
                return $value !== null;
            });
        });

        // Fix start here
        $filteredData = array_filter((array) $filteredRows, function ($row) {
            return !empty($row); // Keep rows that are not empty
        });


        Helpers::infoLog("Test", "Test", $filteredData);
        
        // Iterate through each filtered row and handle the import logic
        foreach ($filteredRows as $row) {
            // Retrieve employee profile based on the code in the Excel row
            $employee = EmployeeProfile::where('employee_id', $row['EmployeeID']);

            // Retrieve the redcap module based on the code in the Excel row
            $redcap_module = RedcapModules::where('code', $row['Code'])->first();
            
            // Parse the URL to get the query string
            // $link = parse_url($row['Link']);

            // Extract query parameters into an associative array
            // parse_str($parsedUrl['query'], $queryParams);

            // Retrieve the 'informant_id' parameter (unique identifier)
            // $employeeAuthId = $queryParams['informant_id'] ?? null;
            $employeeAuthId = $row['Link'];

            // If the redcap module exists, store the data in the EmployeeRedcapModules model
            if ($redcap_module) {
                EmployeeRedcapModules::create([
                    'redcap_module_id' => $redcap_module->id,
                    'employee_profile_id' => $row['employeeid'],
                    'employee_auth_id' => $employeeAuthId,
                    'deactivated_at' => null
                ]);
            } else {
                // Handle missing module, log or handle as necessary
                \Log::warning('Missing RedcapModule for code: ' . $row['code']);
            }
        }
    }
}
