<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Division;
use App\Models\Section;
use Illuminate\Support\Facades\DB;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $division = Division::where('code', 'HOPSS')->first();

        // Section::create([
        //     'name' => 'Data Protection Unit',
        //     'code' => 'DPU',
        //     'division_id' => $division->id
        // ]);

        // Section::create([
        //     'name' => 'Engineering and Facilities Management',
        //     'code' => 'EFM',
        //     'division_id' => $division->id
        // ]);

        // Section::create([
        //     'name' => 'Human Resource Management Office',
        //     'code' => 'HRMO',
        //     'division_id' => $division->id
        // ]);

        // Section::create([
        //     'name' => 'Material Management Section',
        //     'code' => 'MMS',
        //     'division_id' => $division->id
        // ]);

        // Section::create([
        //     'name' => 'Procurement Section',
        //     'code' => 'PROC',
        //     'division_id' => $division->id
        // ]);

        // Section::create([
        //     'name' => 'Security Unit',
        //     'code' => 'SEKYU',
        //     'division_id' => $division->id
        // ]);

        // $division = Division::where('code', 'FINANCE')->first();

        // Section::create([
        //     'name' => 'Budget Section',
        //     'code' => 'BUDGET',
        //     'division_id' => $division->id
        // ]);

        // Section::create([
        //     'name' => 'Accounting Section',
        //     'code' => 'ACCOUNTING',
        //     'division_id' => $division->id
        // ]);

        // Section::create([
        //     'name' => 'Billing and Claims Section',
        //     'code' => 'BILLING',
        //     'division_id' => $division->id
        // ]);

        // Section::create([
        //     'name' => 'Cash Operations Section',
        //     'code' => 'CASH',
        //     'division_id' => $division->id
        // ]);

        $HOPSS = Division::where('code', 'HOPSS')->first();

        $station_hopss = DB::connection('sqlsrv')
            ->table('station')
            ->where('stationname', 'LIKE', '%Human Resource%')
            ->orWhere('stationname', 'LIKE', '%Engineering%')
            ->orWhere('stationname', 'LIKE', '%Data Protection%')
            ->orWhere('stationname', 'LIKE', '%Procurement%')
            ->orWhere('stationname', 'LIKE', '%Material%')
            ->orWhere('stationname', 'LIKE', '%Security%')
            ->get();

        // Filter stations by type
        $HRMO = $station_hopss->filter(function ($station) {
            return stripos($station->stationname, 'Human Resource') !== false;
        })->toArray();

        $EFM = $station_hopss->filter(function ($station) {
            return stripos($station->stationname, 'Engineering') !== false;
        })->toArray();

        $DPU = $station_hopss->filter(function ($station) {
            return stripos($station->stationname, 'Data Protection') !== false;
        })->toArray();

        $PROC = $station_hopss->filter(function ($station) {
            return stripos($station->stationname, 'Procurement') !== false;
        })->toArray();

        $MMS = $station_hopss->filter(function ($station) {
            return stripos($station->stationname, 'Material') !== false;
        })->toArray();

        $SEC = $station_hopss->filter(function ($station) {
            return stripos($station->stationname, 'Security') !== false;
        })->toArray();

        // HRMO
        if (!empty($HRMO)) {
            $HRMOSections = [];
            foreach ($HRMO as $station) {
                $HRMOSections[] = [
                    'id' => $station->stationid,
                    'name' => 'Human Resource Management Office',
                    'code' => 'HRMO',
                    'division_id' => $HOPSS->id
                ];
            }
            Section::insert($HRMOSections);
        }

        // EFM
        if (!empty($EFM)) {
            $EFMSections = [];
            foreach ($EFM as $station) {
                $EFMSections[] = [
                    'id' => $station->stationid,
                    'name' => 'Engineering & Facilities Management Section',
                    'code' => 'EFM',
                    'division_id' => $HOPSS->id
                ];
            }
            Section::insert($EFMSections);
        }

        // DPU
        if (!empty($DPU)) {
            $DPUSections = [];
            foreach ($DPU as $station) {
                $DPUSections[] = [
                    'id' => $station->stationid,
                    'name' => 'Data Protection Unit',
                    'code' => 'DPU',
                    'division_id' => $HOPSS->id
                ];
            }
            Section::insert($DPUSections);
        }

        // PROC
        if (!empty($PROC)) {
            $PROCSections = [];
            foreach ($PROC as $station) {
                $PROCSections[] = [
                    'id' => $station->stationid,
                    'name' => 'Procurement Section',
                    'code' => 'PROC',
                    'division_id' => $HOPSS->id
                ];
            }
            Section::insert($PROCSections);
        }

        // MMS
        if (!empty($MMS)) {
            $MMSSections = [];
            foreach ($MMS as $station) {
                $MMSSections[] = [
                    'id' => $station->stationid,
                    'name' => 'Materials Management Section',
                    'code' => 'MMS',
                    'division_id' => $HOPSS->id
                ];
            }
            Section::insert($MMSSections);
        }

        // MMS
        if (!empty($SEC)) {
            $SECSections = [];
            foreach ($SEC as $station) {
                $SECSections[] = [
                    'id' => $station->stationid,
                    'name' => 'Security Unit',
                    'code' => 'SEC',
                    'division_id' => $HOPSS->id
                ];
            }
            Section::insert($SECSections);
        }

        $FS = Division::where('code', 'FS')->first();

        $station_FS = DB::connection('sqlsrv')
            ->table('station')
            ->where('stationname', 'LIKE', '%accounting%')
            ->orWhere('stationname', 'LIKE', '%billing%')
            ->orWhere('stationname', 'LIKE', '%budget%')
            ->orWhere('stationname', 'LIKE', '%cash%')
            ->get();

        // Filter stations by type
        $accounting_stations = $station_FS->filter(function ($station) {
            return stripos($station->stationname, 'accounting') !== false;
        })->toArray();

        $billing_stations = $station_FS->filter(function ($station) {
            return stripos($station->stationname, 'billing') !== false;
        })->toArray();

        $budget_stations = $station_FS->filter(function ($station) {
            return stripos($station->stationname, 'budget') !== false;
        })->toArray();

        $cash_stations = $station_FS->filter(function ($station) {
            return stripos($station->stationname, 'cash') !== false;
        })->toArray();

        // Accounting Section
        if (!empty($accounting_stations)) {
            $accountingSections = [];
            foreach ($accounting_stations as $station) {
                $accountingSections[] = [
                    'id' => $station->stationid,
                    'name' => 'Accounting Section',
                    'code' => 'ACCOUNTING',
                    'division_id' => $FS->id
                ];
            }
            Section::insert($accountingSections);
        }

        // Billing Section
        if (!empty($billing_stations)) {
            $billingSections = [];
            foreach ($billing_stations as $station) {
                $billingSections[] = [
                    'id' => $station->stationid,
                    'name' => 'Billing and Claims Section',
                    'code' => 'BILLING',
                    'division_id' => $FS->id
                ];
            }
            Section::insert($billingSections);
        }

        // Budget Section
        if (!empty($budget_stations)) {
            $budgetSections = [];
            foreach ($budget_stations as $station) {
                $budgetSections[] = [
                    'id' => $station->stationid,
                    'name' => 'Budget Section',
                    'code' => 'BUDGET',
                    'division_id' => $FS->id
                ];
            }
            Section::insert($budgetSections);
        }

        // Cash Section
        if (!empty($cash_stations)) {
            $cashSections = [];
            foreach ($cash_stations as $station) {
                $cashSections[] = [
                    'id' => $station->stationid,
                    'name' => 'Cash Operations Section',
                    'code' => 'CASH',
                    'division_id' => $FS->id
                ];
            }
            Section::insert($cashSections);
        }

    }
}
