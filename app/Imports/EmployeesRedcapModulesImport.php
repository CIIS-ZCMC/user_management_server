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
        $filteredData = array_filter($filteredRows->toArray(), function ($row) {
            // Check if the row is an array with exactly three elements, all of which are non-empty
            return is_array($row) && count($row) === 3 &&
                   !empty($row[0]) && !empty($row[1]) && !empty($row[2]);
        });

        // Iterate through each filtered row and handle the import logic
        foreach ($filteredData as $row) {
            if($row[0] === 'EmployeeID') continue;

            // Assuming $row is structured with numeric keys: 0 => EmployeeID, 1 => Code, 2 => Link
            $employeeId = $row[0];
            $code = $row[1];
            $employeeAuthId = $row[2];  // Assuming this is the Link column

            // Retrieve employee profile based on the code in the Excel row
            $employee = EmployeeProfile::where('employee_id', $employeeId)->first();

            // Retrieve the redcap module based on the code in the Excel row
            $redcap_module = RedcapModules::where('code', $code)->first();

            // If the redcap module exists, store the data in the EmployeeRedcapModules model
            if ($redcap_module) {
                EmployeeRedcapModules::create([
                    'redcap_module_id' => $redcap_module->id,
                    'employee_profile_id' => $employee->id,
                    'employee_auth_id' => $employeeAuthId,
                    'deactivated_at' => null
                ]);
            } else {
                // Handle missing module, log or handle as necessary
                \Log::warning('Missing RedcapModule for code: ' . $code);
            }
        }
    }
}
