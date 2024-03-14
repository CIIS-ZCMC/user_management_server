<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Resources\ScheduleResource;
use App\Models\EmployeeProfile;
use App\Helpers\Helpers;

use App\Models\EmployeeSchedule;
use App\Models\Schedule;
use App\Models\TimeShift;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeScheduleController extends Controller
{ 
    private $CONTROLLER_NAME = 'Employee Schedule';
    private $PLURAL_MODULE_NAME = 'employee schedules';
    private $SINGULAR_MODULE_NAME = 'employee schedule';

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
    public function create(Request $request)
    {
        try {
            
            $date = Carbon::now();
            $user = $request->user;

            $data = EmployeeProfile::join('personal_informations as PI', 'employee_profiles.personal_information_id', '=', 'PI.id')
                                ->select('employee_profiles.id', 'employee_id', 'biometric_id', DB::raw("CONCAT(PI.first_name, ' ', COALESCE(PI.middle_name, ''), '', PI.last_name) AS name"))
                                ->with([
                                    'schedule' => function ($query) use ($date) {
                                        $query->with(['timeShift', 'holiday'])
                                            ->whereYear('date_start', '=', $date->year)
                                            ->whereMonth('date_start', '=', $date->month);
                                    }
                                ])->firstOrFail($user->id);

            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $is_weekend     = 0;
            $data           = null;
            $user           = $request->user;

            foreach ($cleanData['selected_date'] as $value) {
                // if ($this->hasOverlappingSchedule($value['time_shift_id'], $value['date'], $cleanData['employee_id'])) {
                //     return response()->json(['message' => 'Overlap with existing schedule'], Response::HTTP_FOUND);
                // }

                $schedule = Schedule::where('time_shift_id', $value['time_shift_id'])->where('date', $value['date'])->first();
        
                if ($schedule) {
                    $data = $schedule;
                } else {
                    
                    $dates = Carbon::parse($value['date']);
                    $isWeekend = $dates->dayOfWeek === 6 || $dates->dayOfWeek === 0;

                    if ($isWeekend) {
                        $is_weekend = 1;
                    }

                    $data = new Schedule;

                    $data->time_shift_id    = $value['time_shift_id'];
                    $data->date             = $value['date'];
                    $data->is_weekend       = $is_weekend;
                    $data->save();
                }

                $employee_id = EmployeeProfile::where('id', $cleanData['employee_id'])->pluck('id');
                
                $check_employee_schedules = EmployeeSchedule::where('employee_profile_id', $employee_id)
                                                            ->where('schedule_id', $data->id)
                                                            ->where('deleted_at', null)
                                                            ->first();

                if ($check_employee_schedules !== null) {
                    return response()->json(['message' => 'Schedule Already Exist'], Response::HTTP_FOUND);
                }
                
                $data->employee()->attach($employee_id);

                $employee_schedule = $data->employee()->where('employee_profile_id', $employee_id)->first()->id;
                // Helpers::registerEmployeeScheduleLogs($employee_schedule, $user->id, 'Store');
            }

            // Helpers::registerSystemLogs($request, $data['id'], true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' =>  new ScheduleResource($data),
                'message' => 'New employee schedule registered.'
            ], Response::HTTP_OK);
            
        } catch (\Throwable $th) {
            
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
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

    
    private function hasOverlappingSchedule($timeShiftId, $date, $employees)
    {
        foreach ($employees as $employee) {
            foreach ($employee['employee_id'] as $employeeId) {
                $existingSchedules = EmployeeProfile::find($employeeId)->schedule()->where('date', $date)->where('employee_profile_schedule.deleted_at', null)->get();

                foreach ($existingSchedules as $existingSchedule) {
                    if ($this->checkOverlap($timeShiftId, $existingSchedule->timeShift)) {
                        return true; // Overlapping schedule found
                    }
                }
            }
        }

        return false; // No overlapping schedule found
    }

    private function checkOverlap($newTimeShiftId, $existingTimeShift)
    {
        $newTimeShift = TimeShift::find($newTimeShiftId);

        // Convert time shift times to Carbon instances
        $newStart = Carbon::parse($newTimeShift->first_in);
        $newEnd = $newTimeShift->second_out ? Carbon::parse($newTimeShift->second_out) : Carbon::parse($newTimeShift->first_out);
    
        $existingStart = Carbon::parse($existingTimeShift->first_in);
        $existingEnd = $existingTimeShift->second_out ? Carbon::parse($existingTimeShift->second_out) : Carbon::parse($existingTimeShift->first_out);
    
        // Check for overlap
        return !($newStart >= $existingEnd || $newEnd <= $existingStart);
    }
}
