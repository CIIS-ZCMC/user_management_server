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

            // Leave types mapping
            $leaveTypeCodes = ['fl', 'spl', 'vl', 'sl'];

            foreach ($leaveTypeCodes as $code) {
                if (!isset($row[$code])) {
                    continue;
                }

                $creditValue = (float) $row[$code];

                // Find leave type
                $leaveType = LeaveType::where('code', strtoupper($code))->first();
                if (!$leaveType) {
                    continue;
                }

                // Find existing credit
                $leaveCredit = EmployeeLeaveCredit::where('employee_profile_id', $employeeProfile->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->first();

                $previousCredit = 0;

                if ($leaveCredit) {
                    $previousCredit = $leaveCredit->total_leave_credits;

                    $leaveCredit->update([
                        'total_leave_credits' => $creditValue,
                        'used_leave_credits'  => $leaveCredit->used_leave_credits,
                    ]);
                } else {
                    $leaveCredit = EmployeeLeaveCredit::create([
                        'employee_profile_id' => $employeeProfile->id,
                        'leave_type_id'       => $leaveType->id,
                        'total_leave_credits' => $creditValue,
                        'used_leave_credits'  => 0,
                    ]);
                }

                // Log
                EmployeeLeaveCreditLogs::create([
                    'employee_leave_credit_id' => $leaveCredit->id,
                    'previous_credit'          => $previousCredit,
                    'leave_credits'            => $creditValue,
                    'reason'                   => $leaveCredit->wasRecentlyCreated
                        ? "Leave Credit Starting Balance"
                        : "Leave Credit Updated via Import",
                    'action'                   => $leaveCredit->wasRecentlyCreated ? "add" : "update",
                ]);
            }
        }
    }
}
