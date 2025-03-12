<?php

namespace App\Imports;

use App\Models\OfficialBusiness;
use Maatwebsite\Excel\Concerns\ToModel;

class OfficialBusinessImport implements ToModel
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

        // Parse dates using Carbon for 'Y-m-d' format (matching the yyyy-mm-dd format in Excel)
        $dateFromCarbon = \Carbon\Carbon::createFromFormat('Y-m-d', $dateFrom, 'Asia/Manila');
        $dateToCarbon = \Carbon\Carbon::createFromFormat('Y-m-d', $dateTo, 'Asia/Manila');

        return new OfficialBusiness([
            'employee_profile_id'    => (int)$row[0],
            'date_from'              => $dateFromCarbon,
            'date_to'                => $dateToCarbon,
            'status'                 => $row[3],
            'purpose'                => $row[4],
            'recommending_officer'   => (int)$row[5],
            'approving_officer'      => (int)$row[6],
        ]);
    }
}
