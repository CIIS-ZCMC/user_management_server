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
        return new OfficialTime([
            //
        ]);
    }
}
