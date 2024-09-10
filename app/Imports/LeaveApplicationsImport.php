<?php

namespace App\Imports;

use App\Models\LeaveApplication;

use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;

class LeaveApplicationsImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

        static $headerSkipped = false;

        if (!$headerSkipped) {
            $headerSkipped = true; // Skip the first row
            return null;
        }

        try {
            // Parse the dates with explicit timezone if needed
            $dateFrom = Carbon::createFromFormat('Y-m-d H:i:s', $row[2], 'Asia/Manila');
            $dateTo = Carbon::createFromFormat('Y-m-d H:i:s', $row[3], 'Asia/Manila');
        } catch (\Exception $e) {
            // Handle parsing errors (log or handle as needed)
            $dateFrom = null; // or set a default value
            $dateTo = null;   // or set a default value
        }

        return new LeaveApplication([
            'leave_type_id'          => (int)$row[0],
            'employee_profile_id'    => (int)$row[1],
            'date_from'              => $dateFrom,
            'date_to'                => $dateTo,
            'country'                => $row[4],
            'city'                   => $row[5],
            'is_outpatient'          => (int)$row[6],
            'illness'                => $row[7],
            'is_masters'             => (int)$row[8],
            'is_board'               => (int)$row[9],
            'is_commutation'         => (int)$row[10],
            'applied_credits'        => $row[11],
            'status'                 => $row[12],
            'without_pay'            => (int)$row[13],
            'reason'                 => $row[14],
            'is_printed'             => (int)$row[15],
            'hrmo_officer'           => (int)$row[16],
            'recommending_officer'   => (int)$row[17],
            'approving_officer'      => (int)$row[18],
        ]);
    }
}
