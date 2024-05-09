<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Resources\EmployeeScheduleResource;
use App\Http\Resources\HolidayResource;
use App\Models\AssignArea;
use App\Models\Department;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\MonthlyWorkHours;
use App\Models\Schedule;
use App\Models\EmployeeProfile;
use App\Models\Section;

use App\Http\Resources\ScheduleResource;
use App\Http\Requests\ScheduleRequest;
use App\Helpers\Helpers;

use App\Models\Unit;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

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
            $user = $request->user;
            $month = $request->month;   // Replace with the desired month (1 to 12)
            $year = $request->year;     // Replace with the desired year
            $assigned_area = $user->assignedArea->findDetails();
            $dates_with_day = Helpers::getDatesInMonth($year, $month, "Days of Week");

            $this->updateAutomaticScheduleStatus();

            if ($user->employee_id === "1918091351" || $user->employee_id === "2022100950") {
                return response()->json([
                    'data' => ScheduleResource::collection(EmployeeProfile::all()),
                    'dates' => $dates_with_day,
                ], Response::HTTP_OK);
            }

            $myEmployees = $user->myEmployees($assigned_area, $user);
            $employee_ids = collect($myEmployees)->pluck('id')->toArray();

            $array = EmployeeProfile::with([
                'assignedArea',
                'schedule' => function ($query) use ($year, $month) {
                    $query->with(['timeShift', 'holiday'])
                        ->whereYear('date', '=', $year)
                        ->whereMonth('date', '=', $month);
                }
            ])->whereIn('id', $employee_ids)->get();

            return response()->json([
                'data' => ScheduleResource::collection($array),
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
            $user = $request->user;
            $assigned_area = $user->assignedArea->findDetails();

            $month = $request->month;  // Replace with the desired month (1 to 12)
            $year = $request->year;   // Replace with the desired year

            $dates = Helpers::getDatesInMonth($year, Carbon::parse($month)->month, "");

            //Array
            // $myEmployees = $user->areaEmployee($assigned_area);
            // $supervisors = $user->sectorHeads();

            // $employees = [...$myEmployees, ...$supervisors];
            // $employee_ids = collect($employees)->pluck('id')->toArray();

            $myEmployees = $user->myEmployees($assigned_area, $user);
            $employee_ids = collect($myEmployees)->pluck('id')->toArray();

            $sql = EmployeeProfile::where(function ($query) use ($assigned_area) {
                $query->whereHas('schedule', function ($innerQuery) use ($assigned_area) {
                    $innerQuery->with(['timeShift', 'holiday']);
                });
            })->whereIn('id', $employee_ids)->with(['personalInformation', 'assignedArea', 'schedule.timeShift'])->get();

            $employee = ScheduleResource::collection($sql);

            $RecommendingApprovingOfficer = Helpers::ScheduleApprovingOfficer($assigned_area, $user);
            $recommending_officer = EmployeeProfile::where('id', $RecommendingApprovingOfficer['recommending_officer'])->first();
            $approving_officer = EmployeeProfile::where('id', $RecommendingApprovingOfficer['approving_officer'])->first();
            $holiday = Holiday::all();

            $options = new Options();
            $options->set('isPhpEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->getOptions()->setChroot([base_path() . '/public/storage']);
            $html = view('generate_schedule/section-schedule', compact('employee', 'holiday', 'month', 'year', 'dates', 'user', 'recommending_officer', 'approving_officer'))->render();
            $dompdf->loadHtml($html);

            $dompdf->setPaper('Legal', 'landscape');
            $dompdf->render();
            $filename = 'Schedule.pdf';

            /* Downloads as PDF */
            $dompdf->stream($filename, array("Attachment" => false));
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeList(Request $request)
    {
        try {
            $employees = [];
            $user = $request->user;
            $assigned_area = $user->assignedArea->findDetails();

            //Array
            // Fetch head employee ID using employeeHead function
            $employee_head = $user->employeeHead($assigned_area);

            // Fetch employees from employeeAreaList
            $myEmployees = $user->employeeAreaList($assigned_area);

            // Fetch supervisors
            $supervisors = $user->sectorHeads();
            // Combine all employees and supervisors
            $employees = [...$myEmployees, ...$supervisors];

            // Pluck IDs from all employees
            $employee_ids = collect($employees)->pluck('id')->toArray();

            // Fetch employees from the database based on IDs and exclude certain IDs
            $fetch_employees = EmployeeProfile::whereIn('id', $employee_ids)
                ->where(function ($query) use ($user, $assigned_area, $employee_head) {
                    return $assigned_area['details']['code'] === "HRMO" ?
                        $query->whereNotIn('id', [$user->id, $employee_head, 1]) :
                        $query->whereNotIn('id', [$user->id, $employee_head]);
                })->get();

            // Process fetched employees
            $data = [];
            foreach ($fetch_employees as $employee) {
                $data[] = [
                    'id' => $employee->id,
                    'name' => $employee->name(),
                ];
            }

            return response()->json(['data' => $data], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeeList', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findSchedule(Request $request)
    {
        try {
            $user = $request->user->id;
            $reliever_id = $request->employee_id;

            $user_schedule = EmployeeSchedule::where('employee_profile_id', $user)
                ->whereHas('schedule', function ($query) use ($request) {
                    $query->where('date', $request->date_selected);
                })->first();

            if ($user_schedule !== null) {
                return response()->json([
                    'data' => $request->employee_id,
                    'message' => "Your already have schedule on " . $request->date_selected
                ], Response::HTTP_FOUND);
            }

            $sql = EmployeeSchedule::where('employee_profile_id', $reliever_id)
                ->whereHas('schedule', function ($query) use ($request) {
                    $query->where('date', $request->date_selected);
                })->get();

            $schedule = [];
            foreach ($sql as $value) {
                $schedule[] = [
                    'id' => $value->schedule->id,
                    'start' => $value->schedule->date,
                    'title' => $value->schedule->timeShift->timeShiftDetails(),
                    'color' => $value->schedule->timeShift->color,
                    'status' => $value->schedule->status,
                ];
            }

            $data = [
                'employee_id' => $sql->isEmpty() ? null : $sql->first()->employee_profile_id,
                'schedule' => $schedule,
            ];

            return response()->json(['data' => new EmployeeScheduleResource($data)], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'findSchedule', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function retrieveEmployees($employees, $key, $id, $myId)
    {
        $assign_areas = AssignArea::where($key, $id)
            ->whereNotIn('employee_profile_id', $myId)->get();

        $new_employee_list = $assign_areas->map(function ($assign_area) {
            return $assign_area->employeeProfile;
        })->flatten()->all();

        return [...$employees, ...$new_employee_list];
    }


    public function myAreas(Request $request)
    {
        try {
            $user = $request->user;
            $position = $user->position();

            if (!$position) {
                return response()->json(['message' => "You don't have authorization as a supervisor of area."], Response::HTTP_FORBIDDEN);
            }

            $my_area = $user->assignedArea->findDetails();
            $areas = [];

            switch ($my_area['sector']) {
                case "Division":
                    $areas[] = ['id' => $my_area['details']->id, 'name' => $my_area['details']->name, 'sector' => $my_area['sector']];
                    $deparmtents = Department::where('division_id', $my_area['details']->id)->get();

                    foreach ($deparmtents as $department) {
                        $areas[] = ['id' => $department->id, 'name' => $department->name, 'sector' => 'Department'];

                        $sections = Section::where('department_id', $department->id)->get();
                        foreach ($sections as $section) {
                            $areas[] = ['id' => $section->id, 'name' => $section->name, 'sector' => 'Section'];

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $areas[] = ['id' => $unit->id, 'name' => $unit->name, 'sector' => 'Unit'];
                            }
                        }
                    }
                    break;
                case "Department":
                    $areas[] = ['id' => $my_area['details']->id, 'name' => $my_area['details']->name, 'sector' => $my_area['sector']];
                    $sections = Section::where('department_id', $my_area['details']->id)->get();

                    foreach ($sections as $section) {
                        $areas[] = ['id' => $section->id, 'name' => $section->name, 'sector' => 'Section'];

                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $areas[] = ['id' => $unit->id, 'name' => $unit->name, 'sector' => 'Unit'];
                        }
                    }
                    break;
                case "Section":
                    $areas[] = ['id' => $my_area['details']->id, 'name' => $my_area['details']->name, 'sector' => $my_area['sector']];

                    $units = Unit::where('section_id', $my_area['details']->id)->get();
                    foreach ($units as $unit) {
                        $areas[] = ['id' => $unit->id, 'name' => $unit->name, 'sector' => 'Unit'];
                    }
                    break;
                case "Unit":
                    $areas[] = ['id' => $my_area['details']->id, 'name' => $my_area['details']->name, 'sector' => $my_area['sector']];
                    break;
            }

            return response()->json([
                'data' => $areas,
                'message' => 'Successfully retrieved all my areas.'
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'myAreas', $th->getMessage());
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