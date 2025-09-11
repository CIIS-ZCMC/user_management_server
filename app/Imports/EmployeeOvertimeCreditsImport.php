<?php

namespace App\Imports;

use App\Models\EmployeeProfile;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeOvertimeCreditLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EmployeeOvertimeCreditsImport implements ToCollection, WithHeadingRow
{
    public $created = [];
    public $updated = [];
    public $skipped = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if (empty($row['employee_id'])) {
                $this->skipped[] = ['reason' => 'Missing employee_id', 'row' => $row->toArray()];
                continue;
            }

            $employeeProfile = EmployeeProfile::where('employee_id', $row['employee_id'])->first();
            if (!$employeeProfile) {
                $this->skipped[] = ['reason' => 'Employee not found', 'row' => $row->toArray()];
                continue;
            }

            $creditValue   = (float) ($row['cto'] ?? 0);
            $validUntilRaw = $row['valid_until'] ?? null;

            // Parse valid_until (can be Excel serial or string)
            $validUntil = null;
            if (!empty($validUntilRaw)) {
                try {
                    if (is_numeric($validUntilRaw)) {
                        $validUntil = ExcelDate::excelToDateTimeObject($validUntilRaw)->format('Y-m-d');
                    } else {
                        try {
                            $validUntil = Carbon::createFromFormat('m/d/Y', trim($validUntilRaw))->format('Y-m-d');
                        } catch (\Exception $e) {
                            $validUntil = Carbon::parse($validUntilRaw)->format('Y-m-d');
                        }
                    }
                } catch (\Exception $e) {
                    $this->skipped[] = ['reason' => 'Invalid date format', 'row' => $row->toArray()];
                    $validUntil = null;
                }
            }

            // Find existing CTO with the same employee + valid_until
            $overtimeCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employeeProfile->id)
                ->whereDate('valid_until', $validUntil)
                ->first();

            $previousCredit = 0;

            if ($overtimeCredit) {
                $previousCredit = $overtimeCredit->earned_credit_by_hour;

                $overtimeCredit->update([
                    'earned_credit_by_hour' => $creditValue,
                ]);

                $this->updated[] = [
                    'employee_id'     => $employeeProfile->employee_id,
                    'previous_credit' => $previousCredit,
                    'new_credit'      => $creditValue,
                    'valid_until'     => $validUntil,
                ];
            } else {
                $overtimeCredit = EmployeeOvertimeCredit::create([
                    'employee_profile_id'   => $employeeProfile->id,
                    'earned_credit_by_hour' => $creditValue,
                    'used_credit_by_hour'   => 0,
                    'max_credit_monthly'    => 40,
                    'max_credit_annual'     => 120,
                    'valid_until'           => $validUntil,
                    'is_expired'            => false,
                ]);

                $this->created[] = [
                    'employee_id'   => $employeeProfile->employee_id,
                    'new_credit'    => $creditValue,
                    'valid_until'   => $validUntil,
                ];
            }

            // Log
            EmployeeOvertimeCreditLog::create([
                'employee_ot_credit_id' => $overtimeCredit->id,
                'action'                => $overtimeCredit->wasRecentlyCreated ? "add" : "update",
                'reason'                => $overtimeCredit->wasRecentlyCreated
                    ? "Overtime Credit Starting Balance"
                    : "Overtime Credit Updated via Import",
                'hours'                 => $creditValue,
            ]);
        }
    }
}
