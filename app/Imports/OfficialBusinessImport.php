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
        return new OfficialBusiness([
            //
        ]);
    }
}
