<?php

namespace App\Http\Controllers\Schedule;

use App\Models\Schedule;
use App\Models\EmployeeProfile;
use App\Http\Requests\ScheduleRequest;
use App\Providers\RequestLogger;
use App\Helpers\Helpers;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ScheduleRequest $request)
    {
        try {
           

            $date_start     = Carbon::parse($request['date_start']);    // Replace with your start date
            $date_end       = Carbon::parse($request['date_end']);      // Replace with your end date
            $selected_days  = $request['selected_days'];                // Replace with your selected days
            $selected_dates = [];                                       // Replace with your selected dates

            switch ($request['schedule_type']) {
                case 'dates_only':
                    $current_date = $date_start->copy();

                    while ($current_date->lte($date_end)) {
                        $selected_dates[] = $current_date->toDateString();
                        $current_date->addDay();
                    }
                break;

                case 'days_only' :
                    $year   = Carbon::now()->year;          // Replace with your desired year
                    $month  = $request['selected_month'];   // Replace with your desired month

                    $start_date = Carbon::create($year, $month, 1)->startOfMonth(); // Calculate the first day of the month
                    $end_date   = $start_date->copy()->endOfMonth();                // Calculate the last day of the month

                    $current_date = $start_date->copy();

                    while ($current_date->lte($end_date)) {
                        if (in_array($current_date->englishDayOfWeek, $selected_days)) {
                            $selected_dates[] = $current_date->toDateString();
                        }
                        $current_date->addDay();
                    }

                break;

                default:
                    $current_date = $date_start->copy();
                        
                    while ($current_date->lte($date_end)) {
                        if (in_array($current_date->englishDayOfWeek, $selected_days)) {
                            $selected_dates[] = $current_date->toDateString();
                        }
                        $current_date->addDay();
                    }
                break;
            }
            
            foreach ($selected_dates as $key => $date) {

                $data = new Schedule;

                $data->time_shift_id    = $request['time_shift_id'];
                $data->month            = $request['month'];
                $data->date_start       = $date;
                $data->date_end         = $date;
                $data->is_weekend       = $request['is_weekend'];
                $data->save();

                $employee = $request['employee'];
                foreach ($employee as $key => $value) {
                    $emp        = EmployeeProfile::select('id')->where('id', $value['employee_id'])->first();
                    $emp_sched  = $data->employee()->attach($emp);
                }
            }

            return response()->json([
                'message' => 'Success',
                'data' => $data
            ], 200);

        } catch (\Throwable $th) {

            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $schedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ScheduleRequest $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        //
    }
}
