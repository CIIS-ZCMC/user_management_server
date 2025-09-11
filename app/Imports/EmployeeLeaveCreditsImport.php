<?php

namespace App\Imports;

use App\Models\EmployeeProfile;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeLeaveCreditLogs;
use App\Models\LeaveType;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeOvertimeCreditLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeeLeaveCreditsImport implements ToCollection, WithHeadingRow
{
    public $affected = []; // collect affected employee_profile_ids

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if (empty($row['employee_id'])) {
                continue;
            }

            $employeeProfile = EmployeeProfile::where('employee_id', $row['employee_id'])->first();
            if (!$employeeProfile) {
                continue;
            }

            $leaveTypeCodes = ['fl', 'spl', 'vl', 'sl'];

            foreach ($leaveTypeCodes as $code) {
                if (!isset($row[$code])) {
                    continue;
                }

                $creditValue = (float) $row[$code];
                $leaveType   = LeaveType::where('code', strtoupper($code))->first();
                if (!$leaveType) {
                    continue;
                }

                $leaveCredit = EmployeeLeaveCredit::where('employee_profile_id', $employeeProfile->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->first();

                if ($leaveCredit) {
                    $leaveCredit->update([
                        'total_leave_credits' => $creditValue,
                        'used_leave_credits'  => $leaveCredit->used_leave_credits,
                    ]);
                } else {
                    EmployeeLeaveCredit::create([
                        'employee_profile_id' => $employeeProfile->id,
                        'leave_type_id'       => $leaveType->id,
                        'total_leave_credits' => $creditValue,
                        'used_leave_credits'  => 0,
                    ]);
                }

                // mark employee as affected
                $this->affected[] = $employeeProfile->id;
            }
        }

        // ensure unique IDs only
        $this->affected = array_unique($this->affected);
    }
}
