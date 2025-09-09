<?php

namespace App\Imports;

use App\Models\EmployeeProfile;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeOvertimeCreditLogs; // if you also want logging like leave credits
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\EmployeeOvertimeCreditLog;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;


class EmployeeOvertimeCreditsImport implements ToModel
{
    public function model(array $row)
    {
        static $headerSkipped = false;

        if (!$headerSkipped) {
            $headerSkipped = true; // Skip header row
            return null;
        }

        // Excel columns: [employee_id, earned_credit_by_hour, valid_until]

        // 1. Find employee profile
        $employeeProfile = EmployeeProfile::where('employee_id', $row[0])->first();
        if (!$employeeProfile) {
            return null; // Skip if employee not found
        }

        $validUntil = null;

        if (!empty($row[2])) {
            try {
                if (is_numeric($row[2])) {
                    // Excel stores date as a serial number (e.g. 45720)
                    $validUntil = ExcelDate::excelToDateTimeObject($row[2])->format('Y-m-d');
                } else {
                    // Try dd/mm/YYYY first
                    $validUntil = \Carbon\Carbon::createFromFormat('d/m/Y', trim($row[2]))->format('Y-m-d');
                }
            } catch (\Exception $e) {
                try {
                    // Fallback if Excel gives YYYY-mm-dd
                    $validUntil = \Carbon\Carbon::parse($row[2])->format('Y-m-d');
                } catch (\Exception $e2) {
                    $validUntil = null; // leave null if parsing fails
                }
            }
        }

        $creditValue = $row[1] ?? 0;

        // 3. Check existing overtime credit
        $existingCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employeeProfile->id)
            ->where('valid_until', $validUntil)
            ->first();

        if ($existingCredit) {
            $previousCredit = $existingCredit->earned_credit_by_hour;
            $existingCredit->earned_credit_by_hour = $creditValue;
            $existingCredit->save();

            EmployeeOvertimeCreditLog::create([
                'employee_ot_credit_id' => $existingCredit->id,
                'action'                => 'add',
                'reason'                => 'Imported credit update',
                'hours'                 => $creditValue,
            ]);

            return $existingCredit;
        } else {
            $newCredit = EmployeeOvertimeCredit::create([
                'employee_profile_id'   => $employeeProfile->id,
                'earned_credit_by_hour' => $creditValue,
                'used_credit_by_hour'   => 0,
                'max_credit_monthly'    => 40,
                'max_credit_annual'     => 120,
                'valid_until'           => $validUntil,
                'is_expired'            => false,
            ]);

            EmployeeOvertimeCreditLog::create([
                'employee_ot_credit_id' => $newCredit->id,
                'action'                => 'add',
                'reason'                => 'Imported new credit',
                'hours'                 => $creditValue,
            ]);

            return $newCredit;
        }
    }
}
