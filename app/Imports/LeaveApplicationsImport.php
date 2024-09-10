<?php

namespace App\Imports;

use App\Models\LeaveApplication;
use Maatwebsite\Excel\Concerns\ToModel;

class LeaveApplicationsImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new LeaveApplication([
            'leave_type_id'          => $row[0],  
            'employee_profile_id'    => $row[1],
            'date_from'              => $row[2],
            'date_to'                => $row[3],
            'country'                => $row[4],
            'city'                   => $row[5],
            'is_outpatient'          => $row[6],
            'illness'                => $row[7],
            'is_masters'             => $row[8],
            'is_board'               => $row[9],
            'is_commutation'         => $row[10],
            'applied_credits'        => $row[11],
            'status'                 => $row[12],
            'without_pay'            => $row[13],
            'reason'                 => $row[14],
            'is_printed'             => $row[15],
            'hrmo_officer'           => $row[16],
            'recommending_officer'   => $row[17],
            'approving_officer'      => $row[18],
        ]);
    }
}
