<?php

namespace App\Imports;

use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeLeaveCreditLogs;
use App\Models\EmployeeProfile;
use App\Models\LeaveType;
use Maatwebsite\Excel\Concerns\ToModel;

class EmployeeLeaveCreditsImport implements ToModel
{
    /**
     * Map each row of the CSV to the EmployeeLeaveCredit model.
     *
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        static $headerSkipped = false;

        if (!$headerSkipped) {
            $headerSkipped = true; // Skip header row
            return null;
        }

        // Excel columns: [employee_id, leave_type_name, total_leave_credits, used_leave_credits]

        // 1. Find employee profile by employee_id
        $employeeProfile = EmployeeProfile::where('employee_id', $row[0])->first();
        if (!$employeeProfile) {
            return null; // Skip if no matching employee
        }

        // 2. Find leave type by name
        $leaveType = LeaveType::where('name', $row[1])->first();
        if (!$leaveType) {
            return null; // Skip if no matching leave type
        }

        // 3. Find existing leave credit
        $leaveCredit = EmployeeLeaveCredit::where('employee_profile_id', $employeeProfile->id)
            ->where('leave_type_id', $leaveType->id)
            ->first();

        $previousCredit = 0;

        if ($leaveCredit) {
            // Update existing record
            $previousCredit = $leaveCredit->total_leave_credits;

            $leaveCredit->update([
                'total_leave_credits' => $row[2],
                'used_leave_credits'  =>  $leaveCredit->used_leave_credits,
            ]);
        } else {
            // Create new record
            $leaveCredit = EmployeeLeaveCredit::create([
                'employee_profile_id' => $employeeProfile->id,
                'leave_type_id'       => $leaveType->id,
                'total_leave_credits' => $row[2] ?? null,
                'used_leave_credits'  => 0,
            ]);
        }

        // 4. Log every insert/update
        EmployeeLeaveCreditLogs::create([
            'employee_leave_credit_id' => $leaveCredit->id,
            'previous_credit'          => $previousCredit,
            'leave_credits'            => $row[2] ?? 0,
            'reason'                   => $leaveCredit->wasRecentlyCreated
                ? "Leave Credit Starting Balance"
                : "Leave Credit Updated",
            'action'                   => $leaveCredit->wasRecentlyCreated ? "add" : "update",
        ]);

        return $leaveCredit;
    }
}
