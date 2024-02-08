<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Resources\EmployeeScheduleResource;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\EmployeeProfile;

use App\Http\Resources\ScheduleResource;
use App\Http\Requests\ScheduleRequest;
use App\Helpers\Helpers;

use App\Models\Section;
use App\Models\Unit;
use Dompdf\Dompdf;
use Dompdf\Options;
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

            $array = null;
            if ($assigned_area['details']['code'] === 'HRMO') {
                $array = EmployeeProfile::with([
                    'assignedArea',
                    'schedule' => function ($query) use ($year, $month) {
                        $query->with(['timeShift', 'holiday'])->whereYear('date', '=', $year)->whereMonth('date', '=', $month);
                    }
                ])->get();
            } else {
                $array = EmployeeProfile::with([
                    'assignedArea',
                    'schedule' => function ($query) use ($year, $month) {
                        $query->with(['timeShift', 'holiday'])->whereYear('date', '=', $year)->whereMonth('date', '=', $month);
                    }
                ])->whereHas('assignedArea', function ($query) use ($user, $assigned_area) {
                    $query->where([strtolower($assigned_area['sector']) . '_id' => $user->assignedArea->id]);
                })->get();
            }

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
            return response()->json([
                'data' => EmployeeScheduleResource::collection($model),
                'holiday' => Holiday::all()
            ], Response::HTTP_OK);

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

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $user = $request->user;
            $message = null;
            $data = null;
            $is_weekend = 0;

            $date_start     = $cleanData['date_start'];     // Replace with your start date
            $date_end       = $cleanData['date_end'];       // Replace with your end date
            $selected_days  = $cleanData['selected_days'];  // Replace with your selected days
            $selected_dates = [];                           // Replace with your selected dates

            switch ($selected_days) {
                //If Toggle Date Period On
                case($selected_days <= 0 && $date_start != null && $date_end != null):
                    $current_date = Carbon::parse($date_start)->copy();

                    while ($current_date->lte($date_end)) {
                        $selected_dates[] = $current_date->toDateString();
                        $current_date->addDay();
                    }
                    break;

                    
                //If Toggle Show Day on
                case($selected_days >= 1 && $date_start === null && $date_end === null):
                    $date = Carbon::now();  // Replace with your desired year
                    $month = Carbon::parse($cleanData['month'])->month;   // Replace with your desired month

                    $start_date = Carbon::create($date->year, $month, 1)->startOfMonth();
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
                    $current_date = Carbon::parse($date_start)->copy();

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

                    $data->time_shift_id    = $cleanData['time_shift_id'];
                    $data->is_weekend       = $is_weekend;
                    $data->date             = $date;
                    $data->save();
                } else {

                    $data = $schedule;
                }

                $employee = $cleanData['employee'];
                foreach ($employee as $key => $value) {
                    $employee_ids = $value['employee_id']; // Array of employee IDs
                    // return $existing_employee_ids = EmployeeProfile::whereIn('id', $employee_ids)->pluck('id');
                    $existing_employee_ids = EmployeeProfile::where('id', $employee_ids)->pluck('id');

                    foreach ($existing_employee_ids as $employee_id) {
                        $query = DB::table('employee_profile_schedule')->where([
                            ['employee_profile_id', '=', $employee_id],
                            ['schedule_id', '=', $data->id],
                        ])->first();

                        if ($query) {
                            $message = 'Employee schedule already exists.';
                        } else {
                            $data->employee()->attach($employee_id);
                            $message = 'New employee schedule registered.';
                        }
                    }
                }
            }

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' =>  new ScheduleResource($data),
                'logs' => Helpers::registerEmployeeScheduleLogs($data->id, $user->id, 'Store'),
                'message' => $message
            ], Response::HTTP_OK);

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

            $data->time_shift_id    = $cleanData['time_shift_id'];
            $data->holiday_id       = $cleanData['holiday_id'];
            $data->date             = $cleanData['date'];
            $data->is_weekend       = $cleanData['is_weekend'];
            $data->status           = $cleanData['status'];
            $data->remarks          = $cleanData['remarks'];
            $data->update();

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' =>  new EmployeeScheduleResource($data),
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
            $user           = $request->user;
            $assigned_area  = $user->assignedArea->findDetails();

            $month  = $request->month;  // Replace with the desired month (1 to 12)
            $year   = $request->year;   // Replace with the desired year

            $dates = Helpers::getDatesInMonth($year, Carbon::parse($month)->month, "");

            $data = EmployeeProfile::where(function ($query) use ($user, $assigned_area) {
                                        $query->whereHas('assignedArea', function ($innerQuery) use ($user, $assigned_area) {
                                            $innerQuery->where([strtolower($assigned_area['sector']) . '_id' => $user->assignedArea->id]);
                                        });
                                    })->with(['personalInformation','assignedArea','schedule.timeShift'])->get();

            $approving_officer  = Helpers::ScheduleApprovingOfficer($assigned_area, $user);
            $head_officer       = EmployeeProfile::where('id', $approving_officer['approving_officer'])->first();
            $holiday            = Holiday::all();
    
            $options = new Options();
            $options->set('isPhpEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->getOptions()->setChroot([base_path() . '/public/storage']);
            $html = view('generate_schedule/section-schedule', compact('data','holiday', 'month', 'year', 'dates', 'user', 'head_officer'))->render();
            $dompdf->loadHtml($html);

            $dompdf->setPaper('Legal', 'landscape');
            $dompdf->render();
            $filename = 'Schedule.pdf';

            /* Downloads as PDF */
            $dompdf->stream($filename);
         
            // return view('generate_schedule/section-schedule', compact('data','holiday', 'month', 'year', 'dates', 'user', 'head_officer'));
        } catch (\Throwable $th) {
            
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
