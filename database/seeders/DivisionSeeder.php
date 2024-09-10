<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Division;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Division::create([
        //     'name' => 'Office of Medical Center Chief',
        //     'code' => 'OMCC'
        // ]);

        // Division::create([
        //     'name' => 'Medical Service',
        //     'code' => 'MS'
        // ]);

        // Division::create([
        //     'name' => 'Hospital Operations and Patient Support System',
        //     'code' => 'HOPSS'
        // ]);

        // Division::create([
        //     'name' => 'Nursing Service',
        //     'code' => 'NURSING'
        // ]);

        // Division::create([
        //     'name' => 'Finance Service',
        //     'code' => 'FINANCE'
        // ]);

        $sql_division = DB::connection('sqlsrv')->table('department')->get();

        // Filter stations by type
        $OMCC = $sql_division->filter(function ($division) {
            return stripos($division->code, 'OMCC') !== false;
        })->toArray();

        $MS = $sql_division->filter(function ($division) {
            return stripos($division->code, 'MS') !== false;
        })->toArray();

        $HOPSS = $sql_division->filter(function ($division) {
            return stripos($division->code, 'HOPSS') !== false;
        })->toArray();

        $NS = $sql_division->filter(function ($division) {
            return stripos($division->code, 'NS') !== false;
        })->toArray();

        $FS = $sql_division->filter(function ($division) {
            return stripos($division->code, 'FS') !== false;
        })->toArray();

        if (!empty($OMCC)) {
            $OMCCDivision = [];
            foreach ($OMCC as $division) {
                $OMCCDivision[] = [
                    'id' => $division->departmentid,
                    'name' => 'Office of Medical Center Chief',
                    'code' => 'OMCC',
                ];
            }
            Division::insert($OMCCDivision);
        }

        if (!empty($MS)) {
            $MSDivision = [];
            foreach ($MS as $division) {
                $MSDivision[] = [
                    'id' => $division->departmentid,
                    'name' => 'Medical Service',
                    'code' => 'MS',
                ];
            }
            Division::insert($MSDivision);
        }

        if (!empty($HOPSS)) {
            $HOPSSDivision = [];
            foreach ($HOPSS as $division) {
                $HOPSSDivision[] = [
                    'id' => $division->departmentid,
                    'name' => 'Hospital Operations & Patient Support Service',
                    'code' => 'HOPSS',
                ];
            }
            Division::insert($HOPSSDivision);
        }

        if (!empty($NS)) {
            $NSDivision = [];
            foreach ($NS as $division) {
                $NSDivision[] = [
                    'id' => $division->departmentid,
                    'name' => 'Nursing Service',
                    'code' => 'NS',
                ];
            }
            Division::insert($NSDivision);
        }

        if (!empty($FS)) {
            $FSDivision = [];
            foreach ($FS as $division) {
                $FSDivision[] = [
                    'id' => $division->departmentid,
                    'name' => 'Finance Service',
                    'code' => 'FS',
                ];
            }
            Division::insert($FSDivision);
        }

        // FOR ALLIED
    }
}
