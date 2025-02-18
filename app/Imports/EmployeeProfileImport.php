<?php

namespace App\Imports;

use App\Models\EmployeeProfile;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToArray;

class EmployeeProfileImport implements ToArray
{
    public function array(array $rows)
    {
        return $rows;
    }
}
