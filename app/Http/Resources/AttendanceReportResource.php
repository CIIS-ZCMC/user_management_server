<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'employee_biometric_id' => $this->employeeProfile->biometric_id,
            'employee_id' => $this->employeeProfile->employee_id,
            'employee_name' => $this->employeeProfile->personalInformation->employeeName(),
            'employment_type' => $this->employeeProfile->employmentType->name,
            'employee_designation_name' => $this->employeeProfile->findDesignation()['name'] ?? '',
            'employee_designation_code' => $this->employeeProfile->findDesignation()['code'] ?? '',
            'sector' => $this->employeeProfile->assignedArea->findDetails()['sector'] ?? '',
            'area_name' => $this->employeeProfile->assignedArea->findDetails()['details']['name'] ?? '',
            'area_code' => $this->employeeProfile->assignedArea->findDetails()['details']['code'] ?? '',
            'dtr_count' => $this->employeeProfile->dailyTimeRecords->count(), // Add this line
            'dtr' => $this->dailyTimeRecords,
            // 'from' => $start_day,
            // 'to' => $end_day,
            // 'month' => $month_of,
            // 'year' => $year_of,
            // 'total_working_minutes' => $total_month_working_minutes,
            // 'total_working_hours' => ReportHelpers::ToHours($total_month_working_minutes),
            // 'total_undertime_minutes' => $total_month_undertime_minutes,
            // 'total_days_with_tardiness' => $total_days_with_tardiness,
            // 'total_absent_days' => $number_of_absences,
            // 'total_hours_missed' => $total_hours_missed,
            // 'total_leave_without_pay' => count($leave_without_pay),
            // 'total_leave_with_pay' => count($leave_with_pay),
            // 'schedule' => count($scheduledDays),
        ];
    }
}
