<?php

namespace App\Imports;

use App\Models\OfficialTime;
use Maatwebsite\Excel\Concerns\ToModel;

class OfficialTimeImport implements ToModel
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

        $dateFrom = trim($row[1]);
        $dateTo = trim($row[2]);
        $timeFrom = trim($row[3]);  // Assuming time_from is in column 4
        $timeTo = trim($row[4]);    // Assuming time_to is in column 5

        // Parse dates using Carbon for 'Y-m-d' format (matching the yyyy-mm-dd format in Excel)
        $dateFromCarbon = \Carbon\Carbon::createFromFormat('Y-m-d', $dateFrom, 'Asia/Manila');
        $dateToCarbon = \Carbon\Carbon::createFromFormat('Y-m-d', $dateTo, 'Asia/Manila');

        // Optionally, you can also combine date and time if needed using 'Y-m-d H:i:s'
        $timeFromCarbon = \Carbon\Carbon::createFromFormat('H:i:s', $timeFrom, 'Asia/Manila');
        $timeToCarbon = \Carbon\Carbon::createFromFormat('H:i:s', $timeTo, 'Asia/Manila');

        return new OfficialTime([
            'employee_profile_id'    => (int)$row[0],
            'date_from'              => $dateFromCarbon,
            'date_to'                => $dateToCarbon,
            'time_from'              => $timeFromCarbon,  // Storing time separately
            'time_to'                => $timeToCarbon,    // Storing time separately
            'status'                 => $row[5],
            'purpose'                => $row[6],
            'recommending_officer'   => (int)$row[7],
            'approving_officer'      => (int)$row[8],
        ]);
    }
}
