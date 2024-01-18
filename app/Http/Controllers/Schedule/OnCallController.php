<?php

namespace App\Http\Controllers\Schedule;

use App\Models\Schedule;
use App\Models\EmployeeProfile;

use App\Http\Resources\ScheduleResource;
use App\Http\Requests\ScheduleRequest;
use App\Helpers\Helpers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use DateTime;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OnCallController extends Controller
{
    private $CONTROLLER_NAME = 'On Call Schedule';
    private $PLURAL_MODULE_NAME = 'on call schedules';
    private $SINGULAR_MODULE_NAME = 'on call schedule';
    /**
     * Display a listing of the resource.
     */
    public function index()
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
    public function store(Request $request)
    {
        try {

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (empty($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (is_array($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (DateTime::createFromFormat('Y-m-d', $value)) {
                    $cleanData[$key] = Carbon::parse($value);
                    continue;
                }

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $user       = $request->user;
            $data       = null;
            $msg        = null;
            $is_weekend = 0;
            
            if ($user != null && $user->position()) {
                $position = $user->position();

                if ($position->position === "Chief" || $position->position === "Department OIC" || $position->position === "Supervisor" || $position->position === "Section OIC" || $position->position === "Unit Head" || $position->position === "Unit OIC") {
                    
                    $schedule = Schedule::where('time_shift_id', $cleanData['time_shift_id'])
                                        ->where('date', $cleanData['date'])
                                        ->first();

                    if (!$schedule) {
                        $date = Carbon::parse($cleanData['date']);
                        $isWeekend = $date->dayOfWeek === 6 || $date->dayOfWeek === 0;

                        if ($isWeekend) {
                            $is_weekend = 1;
                        }

                        $data = new Schedule;
                        $data->time_shift_id    = $cleanData['time_shift_id'];
                        $data->date             = $cleanData['date'];
                        $data->remarks          = $cleanData['remarks'];
                        $data->is_weekend       = $is_weekend;
                        $data->save();
                    } else { 
                        $data = $schedule;
                    }

                    $employee_id  = EmployeeProfile::select('id')->where('id', $cleanData['employee_id'])->first();
                    if ($employee_id != null) {

                        $query = DB::table('employee_profile_schedule')->where([
                            ['employee_profile_id', '=', $employee_id],
                            ['schedule_id', '=', $data->id],
                        ])->first();
    
                        if ($query) {
                            $msg = 'employee schedule already exist';

                        } else {
                            $data->employee()->attach($employee_id, ['is_on_call' => true]);
                            $msg = 'New employee schedule registered.';
                        }
                    }

                    Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
                    return response()->json(['data' => $data ,'message' => $msg], Response::HTTP_OK);
                } 
            } 

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
