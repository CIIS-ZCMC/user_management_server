<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Requests\ScheduleRequest;
use App\Http\Resources\EmployeeScheduleResource;
use App\Http\Resources\HolidayResource;
use App\Http\Resources\ScheduleResource;
use App\Models\EmployeeProfile;
use App\Helpers\Helpers;

use App\Models\EmployeeSchedule;
use App\Models\Holiday;
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
        try {
            $user = $request->user;
            $month = $request->month;   // Replace with the desired month (1 to 12)
            $year = $request->year;     // Replace with the desired year
            $assigned_area = $user->assignedArea->findDetails();
            $dates_with_day = Helpers::getDatesInMonth($year, $month, "Days of Week");

            $this->updateAutomaticScheduleStatus();

            if ($user->employee_id === "1918091351" || $assigned_area['details']['code'] === 'HRMO') {
                $data = EmployeeProfile::where('id', '!=', 1)->get();

                // Calculate total working hours for each employee
                $data->each(function ($employee) use ($year, $month) {
                    $employee->total_working_hours = $employee->schedule->sum(function ($schedule) {
                        return $schedule->timeShift->total_hours ?? 0;
                    });
                });

                return response()->json([
                    'data' => ScheduleResource::collection($data),
                    'dates' => $dates_with_day,
                ], Response::HTTP_OK);
            }

            $myEmployees = $user->myEmployees($assigned_area, $user);
            $employee_ids = collect($myEmployees)->pluck('id')->toArray();

            $data = EmployeeProfile::with([
                'assignedArea',
                'schedule' => function ($query) use ($year, $month) {
                    $query->with(['timeShift', 'holiday'])
                        ->whereYear('date', '=', $year)
                        ->whereMonth('date', '=', $month);
                }
            ])->whereIn('id', $employee_ids)->get();

            // Calculate total working hours for each employee
            $data->each(function ($employee) use ($year, $month) {
                $employee->total_working_hours = $employee->schedule->sum(function ($schedule) {
                    return $schedule->timeShift->total_hours ?? 0;
                });
            });

            return response()->json([
                'data' => ScheduleResource::collection($data),
                'dates' => $dates_with_day,
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
            $user = $request->user;
            // API For Personal Calendar
            $model = EmployeeSchedule::where('employee_profile_id', $user->id)->get();

            $schedule = [];
            foreach ($model as $value) {
                $schedule[] = [
                    'id' => $value->schedule->timeShift->id,
                    'start' => $value->schedule->date,
                    'title' => $value->schedule->timeShift->timeShiftDetails(),
                    'color' => $value->schedule->timeShift->color,
                    'status' => $value->schedule->status,
                ];
            }

            $data = [
                'employee_id' => $model->isEmpty() ? null : $model->first()->employee_profile_id,
                'schedule' => $schedule,
            ];

            return response()->json([
                'data' => new EmployeeScheduleResource($data),
                'holiday' => HolidayResource::collection(Holiday::all())
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'create', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ScheduleRequest $request)
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

            $employee = $cleanData['employee'];
            $selected_date = $cleanData['selected_date'];   // Selected Date;

            $schedule = null;
            $employee_schedules = null;

            if ($selected_date === "") {
                // foreach ($employees as $employee) {
                $existing_employee_ids = EmployeeProfile::where('id', $employee)->pluck('id');

                foreach ($existing_employee_ids as $employee_id) {
                    $employee_schedules = EmployeeSchedule::where('employee_profile_id', $employee_id)
                        // ->whereHas('schedule', function ($query) use ($employee_id) {
                        //     $query->whereYear('date', '=', $year)
                        //         ->whereMonth('date', '=', $month);
                        // })
                        ->first();
                    $employee_schedules->delete();
                }
                // }
            } else {
                // Delete existing data for the selected dates and time shifts
                $schedule = EmployeeSchedule::where('employee_profile_id', $employee)->get();
                foreach ($schedule as $value) {
                    $value->forceDelete();
                }

                // Save new data
                foreach ($selected_date as $selectedDate) {
                    $timeShiftId = $selectedDate['time_shift_id'];

                    foreach ($selectedDate['date'] as $date) {

                        $existingSchedule = EmployeeSchedule::where('employee_profile_id', $employee)->whereHas('schedule', function ($query) use ($timeShiftId, $date) {
                            $query->where('time_shift_id', $timeShiftId)
                                ->where('date', $date);
                        })->exists();

                        if ($existingSchedule) {
                            return response()->json(['message' => 'Duplicates of schedules are not allowed. Please check the date: ' . $date], Response::HTTP_FOUND);
                        }

                        $moreThanOneSchedule = EmployeeSchedule::where('employee_profile_id', $employee)->whereHas('schedule', function ($query) use ($date) {
                            $query->where('date', $date);
                        })->exists();

                        if ($moreThanOneSchedule) {
                            return response()->json(['message' => 'Oops! Only 1 schedule per day. Please check the date:' . $date], Response::HTTP_FOUND);
                        }

                        $schedule = Schedule::where('time_shift_id', $selectedDate['time_shift_id'])
                            ->where('date', $date)
                            ->first();

                        if (!$schedule) {
                            // Create a new schedule if it doesn't exist
                            $isWeekend = (Carbon::parse($date))->isWeekend();

                            $schedule = new Schedule;
                            $schedule->time_shift_id = $timeShiftId;
                            $schedule->date = $date;
                            $schedule->is_weekend = $isWeekend ? 1 : 0;
                            $schedule->save();
                        }

                        // Attach employees to the schedule
                        $schedule->employee()->attach($employee);
                    }
                }
            }

            // Helpers::registerSystemLogs($request, $schedule['id'], true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                // 'data' => new EmployeeScheduleResource($schedule),
                'message' => 'New employee schedule registered.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        try {
            $model = EmployeeSchedule::where('employee_profile_id', $id)->get();

            $schedule = [];
            foreach ($model as $value) {
                $schedule[] = [
                    'id' => $value->schedule->timeShift->id,
                    'start' => $value->schedule->date,
                    'title' => $value->schedule->timeShift->timeShiftDetails(),
                    'color' => $value->schedule->timeShift->color,
                    'status' => $value->schedule->status,
                ];
            }

            $data = [
                'employee_id' => $model->isEmpty() ? null : $model->first()->employee_profile_id,
                'schedule' => $schedule,
            ];

            return response()->json([
                'data' => new EmployeeScheduleResource($data),
                'holiday' => Holiday::all(),
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'edit', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $data = EmployeeSchedule::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (empty($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $schedule = Schedule::where('date', $cleanData['date'])->where('time_shift_id', $cleanData['time_shift_id'])->first();

            if ($schedule === null) {
                $is_weekend = 0;
                $date = Carbon::parse($cleanData['date']);
                $isWeekend = $date->dayOfWeek === 6 || $date->dayOfWeek === 0;

                if ($isWeekend) {
                    $is_weekend = 1;
                }

                $schedule = new Schedule;

                $schedule->time_shift_id = $cleanData['time_shift_id'];
                $schedule->is_weekend = $is_weekend;
                $schedule->date = $cleanData['date'];
                $schedule->save();
            }

            $data->schedule_id = $schedule->id;
            $data->update();

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new EmployeeScheduleResource($data),
                'logs' => Helpers::registerEmployeeScheduleLogs($data->id, $request->user->id, 'Update'),
                'message' => 'Schedule is updated'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, Request $request)
    {
        try {
            $data = EmployeeSchedule::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $data->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in delete ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json(['message' => 'Schedule Succesfully Deleted'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function updateAutomaticScheduleStatus()
    {
        $date_now = Carbon::now();
        $data = Schedule::whereDate('date', '<', $date_now->format('Y-m-d'))->get();

        if (!$data->isEmpty()) { // Check if the collection is not empty
            foreach ($data as $schedule) {
                $schedule->status = false;
                $schedule->save(); // or $schedule->update(['status' => false]);
            }
        }
    }
}
