<?php

namespace App\Imports;

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
        // Iterate through each row and handle the import logic
        foreach ($rows as $row) {
            // Retrieve the redcap module based on the code in the Excel row
            $redcap_module = RedcapModules::where('code', $row['code'])->first();
            
            // Parse the URL to get the query string
            $parsedUrl = parse_url($row['link']);

            // Extract query parameters into an associative array
            parse_str($parsedUrl['query'], $queryParams);

            // Retrieve the 'informant_id' parameter (unique identifier)
            $employeeAuthId = $queryParams['informant_id'] ?? null;

            // If the redcap module exists, store the data in the EmployeeRedcapModules model
            if ($redcap_module) {
                EmployeeRedcapModules::create([
                    'employee_profile_id' => $row['employeeid'],
                    'employee_auth_id' => $employeeAuthId,
                    'redcap_module_id' => $redcap_module->id,
                    'deactivated_at' => null, // Assuming this is null by default
                ]);
            } else {
                // Handle missing module, log or handle as necessary
                \Log::warning('Missing RedcapModule for code: ' . $row['code']);
            }
        }
    }
}
