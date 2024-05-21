<?php

namespace App\Http\Controllers\Migration;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Division;
use App\Models\Section;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MigrateStationController extends Controller
{
    public function fetchStation()
    {
        try {

            $divisions = Division::all();
            $departments = Department::all();
            $sections = Section::all();
            $units = Unit::all();

            $all_areas = [];

            foreach ($divisions as $division) {
                $area = [
                    'id' => $division->id,
                    'name' => $division->name,
                    'code' => $division->code,
                    'sector' => 'division'
                ];
                $all_areas[] = $area;
            }

            foreach ($departments as $department) {
                $area = [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'sector' => 'department'
                ];
                $all_areas[] = $area;
            }

            foreach ($sections as $section) {
                $area = [
                    'id' => $section->id,
                    'name' => $section->name,
                    'code' => $section->code,
                    'sector' => 'section'
                ];
                $all_areas[] = $area;
            }

            foreach ($units as $unit) {
                $area = [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'code' => $unit->code,
                    'sector' => 'unit'
                ];
                $all_areas[] = $area;
            }

            $sql_station = DB::connection('sqlsrv')
                ->table('station')
                ->where('stationname', 'LIKE', '%accounting%')
                ->orWhere('stationname', 'LIKE', '%billing%')
                ->orWhere('stationname', 'LIKE', '%budget%')
                ->orWhere('stationname', 'LIKE', '%cash%')
                ->get();

            // Filter stations with the name 'accounting'
            $accounting_stations = $sql_station->filter(function ($station) {
                return stripos($station->stationname, 'accounting') !== false;
            })->toArray();

            // Filter stations with the name 'billing'
            $billing_stations = $sql_station->filter(function ($station) {
                return stripos($station->stationname, 'billing') !== false;
            })->toArray();

            // Filter stations with the name 'budget'
            $budget_stations = $sql_station->filter(function ($station) {
                return stripos($station->stationname, 'budget') !== false;
            })->toArray();

            // Filter stations with the name 'budget'
            $cash_stations = $sql_station->filter(function ($station) {
                return stripos($station->stationname, 'cash') !== false;
            })->toArray();


            return response()->json([
                'areas' => $all_areas,
                'stations' => [
                    'accounting' => array_column($accounting_stations, 'stationid'), // Extracting station IDs
                    'billing' => $billing_stations, // Entire station data
                    'budget' => $budget_stations, // Entire station data
                    'cash' => $cash_stations // Entire station data
                ]
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
