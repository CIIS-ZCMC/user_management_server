<?php

namespace App\Http\Controllers\Schedule;

use App\Models\EmployeeSchedule;
use App\Models\PullOut;
use App\Models\Holiday;
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

class ScheduleController extends Controller
{
    private $CONTROLLER_NAME = 'Schedule';
    private $PLURAL_MODULE_NAME = 'schedules';
    private $SINGULAR_MODULE_NAME = 'schedule';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {


            $month = $request->month;   // Replace with the desired month (1 to 12)
            $year = $request->year;     // Replace with the desired year
            $dates_with_day = Helpers::getDatesInMonth($year, $month, "Days of Week");

            $user = $request->user;
            $assigned_area = $user->assignedArea->findDetails();

            $array = EmployeeProfile::with(['personalInformation','assignedArea',
                'schedule' => function ($query) use ($year, $month) {
                    $query->with(['timeShift', 'holiday'])->whereYear('date', '=', $year)->whereMonth('date', '=', $month);
                }])->whereHas('assignedArea', function ($query) use ($user, $assigned_area) {
                    $query->where([strtolower($assigned_area['sector']) . '_id' => $user->assignedArea->id]);
                })->get();

            $data = [];
            foreach ($array as $key => $value) {
                $data[] = [
                    'id' => $value['id'],
                    'name' => $value->name(),
                    'employee_id' => $value['employee_id'],
                    'biometric_id' => $value['biometric_id'],
                    'assigned_area' => $value->assignedArea,
                    'schedule' => $value['schedule'],
                ];
            }

            return response()->json(['data' => $data, 'dates' => $dates_with_day], Response::HTTP_OK);

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
            return response()->json(['data' => ScheduleResource::collection($model)], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
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

            $user = $request->user;
            $msg = null;
            $is_weekend = 0;

            // if ($user != null && $user->position()) {
            //     $position = $user->position();

            //     if ($position->position === "Chief" || $position->position === "Department OIC" || $position->position === "Supervisor" || $position->position === "Section OIC" || $position->position === "Unit Head" || $position->position === "Unit OIC") {
            $date_start = $cleanData['date_start'];     // Replace with your start date
            $date_end = $cleanData['date_end'];       // Replace with your end date
            $selected_days = $cleanData['selected_days'];  // Replace with your selected days
            $selected_dates = [];                           // Replace with your selected dates

            switch ($selected_days) {
                //If Toggle Date Period On
                case ($selected_days <= 0 && $date_start != null && $date_end != null):
                    $current_date = $date_start->copy();

                    while ($current_date->lte($date_end)) {
                        $selected_dates[] = $current_date->toDateString();
                        $current_date->addDay();
                    }
                    break;

                //If Toggle Show Day on
                case ($selected_days >= 1 && $date_start === null && $date_end === null):
                    $date = Carbon::now();  // Replace with your desired year
                    $month = Carbon::parse($cleanData['month'])->month;  // Replace with your desired month

                    $firstDayOfMonth = $date->firstOfMonth();

                    // If you want to get the first day of a specific month, you can do something like this:
                    $specificMonth = $month; // Replace with the desired month
                    $start_date = Carbon::create($date->year, $month, 1)->firstOfMonth();
                    $end_date = $start_date->copy()->endOfMonth();

                    $current_date = $start_date->copy();

                    while ($current_date->lte($end_date->startOfDay())) {
                        if (in_array($current_date->englishDayOfWeek, $selected_days)) {
                            $selected_dates[] = $current_date->toDateString();
                        }
                        $current_date->addDay();
                    }
                    break;

                //If Toggle Date Period && Toggle Show Day On
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
                $schedule = Schedule::where('time_shift_id', $cleanData['time_shift_id'])
                    ->where('date', $date)
                    ->first();

                if (!$schedule) {
                    $date = Carbon::parse($date);
                    $isWeekend = $date->dayOfWeek === 6 || $date->dayOfWeek === 0;

                    if ($isWeekend) {
                        $is_weekend = 1;
                    }

                    $data = new Schedule;

                    $data->time_shift_id = $cleanData['time_shift_id'];
                    $data->is_weekend = $is_weekend;
                    $data->date = $date;
                    $data->save();
                } else {

                    $data = $schedule;
                }

                $employee = $cleanData['employee'];
                foreach ($employee as $key => $value) {
                    $employee_id = EmployeeProfile::select('id')->where('id', $value['employee_id'])->first();

                    if ($employee != null) {

                        $query = DB::table('employee_profile_schedule')->where([
                            ['employee_profile_id', '=', $employee_id],
                            ['schedule_id', '=', $data->id],
                        ])->first();

                        if ($query) {
                            $msg = 'employee schedule already exist';

                        } else {
                            $data->employee()->attach($employee_id, ['is_on_call' => $value['is_on_call']]);
                            $msg = 'New employee schedule registered.';
                        }
                    }
                }
            }

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json(['data' => $data, 'message' => $msg], Response::HTTP_OK);
            //     }
            // }

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        try {
            $data = new ScheduleResource(Schedule::findOrFail($id));

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
    public function update(Request $request, $id)
    {
        try {
            $data = Schedule::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (empty($value)) {
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

            $data->time_shift_id = $cleanData['time_shift_id'];
            $data->holiday_id = $cleanData['holiday_id'];
            $data->date = $cleanData['date'];
            $data->is_weekend = $cleanData['is_weekend'];
            $data->status = $cleanData['status'];
            $data->remarks = $cleanData['remarks'];
            $data->update();

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $data = Schedule::withTrashed()->findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $data->employee()->detach($request->employee_id);

            Helpers::registerSystemLogs($request, $id, true, 'Success in delete ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json(['data' => $data], Response::HTTP_OK);
        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Generate PDF file of schedule
     */
    public function generate(Request $request)
    {
        try {

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (empty($value)) {
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

            $user = $request->user;
            if ($user != null && $user->position()) {
                $position = $user->position();

                if ($position->position === "Chief" || $position->position === "Department OIC" || $position->position === "Supervisor" || $position->position === "Section OIC" || $position->position === "Unit Head" || $position->position === "Unit OIC") {

                    $month = Carbon::parse($cleanData['month'])->month;    // Replace with the desired month (1 to 12)
                    $year = Carbon::parse($cleanData['year'])->year;      // Replace with the desired year

                    $days = Helpers::getDatesInMonth($year, $month, "Day");
                    $weeks = Helpers::getDatesInMonth($year, $month, "Week");
                    $dates = Helpers::getDatesInMonth($year, $month, "");

                    $data = EmployeeProfile::join('personal_informations as PI', 'employee_profiles.personal_information_id', '=', 'PI.id')
                        ->with([
                            'assignedArea',
                            'schedule' => function ($query) use ($year, $month) {
                                $query->with(['timeShift', 'holiday'])->whereYear('date', '=', $year)->whereMonth('date', '=', $month);
                            }
                        ])
                        ->whereHas('assignedArea', function ($query) use ($user) {
                            $query->where('id', $user->id);
                        })
                        ->select('employee_profiles.id', 'employee_id', 'biometric_id', 'PI.first_name', 'PI.middle_name', 'PI.last_name')
                        ->get();

                    $holiday = Holiday::all();

                    $pull_out = PullOut::all();

                    $chief = null;
                    $head = null;
                    $supervisor = null;
                    $unit_head = null;

                    $area = $user->assignedArea->findDetails();
                    if ($area != null) {
                        if ($area['sector'] === 'Division') {
                            $chief = $user->assignedArea->division->divisionHead;
                        }

                        if ($area['sector'] === 'Department') {
                            $head = $user->assignedArea->department->head;
                        }

                        if ($area['sector'] === 'Section') {
                            $supervisor = $user->assignedArea->department->supervisor;
                        }

                        if ($area['sector'] === 'Unit') {
                            $unit_head = $user->assignedArea->department->head;
                        }
                    }

                    Helpers::registerSystemLogs($request, $data->id, true, 'Success in generate ' . $this->SINGULAR_MODULE_NAME . '.');
                    return view('generate_schedule/section-schedule', compact('data', 'holiday', 'pull_out', 'month', 'year', 'days', 'weeks', 'dates', 'user', 'chief', 'head', 'supervisor', 'unit_head'));
                } else {
                    return response()->json(['message' => 'User not allowed to create'], Response::HTTP_OK);
                }

            } else {
                return response()->json(['message' => 'User no position'], Response::HTTP_OK);
            }

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
