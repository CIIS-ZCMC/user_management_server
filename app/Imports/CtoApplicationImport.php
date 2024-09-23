<?php

namespace App\Imports;

use App\Models\CtoApplication;
use Maatwebsite\Excel\Concerns\ToModel;

class CtoApplicationImport implements ToModel
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




        // If the length is 10 (meaning the date is in 'Y-m-d' format), append ' 00:00:00' for the time part
        $dateFrom = trim($row[1]);

        // Ensure the date is in the correct format
        $dateFromCarbon = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom, 'Asia/Manila');


        return new CtoApplication([
            'employee_profile_id'    => (int)$row[0],
            'date'                   => $dateFromCarbon,
            'applied_credits'        => (int)$row[2],
            'is_am'                  => (int)$row[3],
            'is_pm'                  => (int)$row[4],
            'status'                 => $row[5],
            'purpose'                => $row[6],
            'recommending_officer'   => (int)$row[7],
            'approving_officer'      => (int)$row[8],
        ]);
    }
}
