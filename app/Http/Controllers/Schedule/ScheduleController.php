<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Resources\EmployeeScheduleResource;
use App\Http\Resources\HolidayResource;
use App\Http\Resources\TimeShiftResource;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\EmployeeProfile;

use App\Http\Resources\ScheduleResource;
use App\Http\Requests\ScheduleRequest;
use App\Helpers\Helpers;

use App\Models\TimeShift;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
            $employees = [];
            $user = $request->user;
            $month = $request->month;   // Replace with the desired month (1 to 12)
            $year = $request->year;     // Replace with the desired year
            $assigned_area = $user->assignedArea->findDetails();
            $dates_with_day = Helpers::getDatesInMonth($year, $month, "Days of Week");

            $this->updateAutomaticScheduleStatus();

            //Array
            $myEmployees = $user->areaEmployee($assigned_area);
            $supervisors = $user->sectorHeads();

            $employees = [...$myEmployees, ...$supervisors];
            $employee_ids = collect($employees)->pluck('id')->toArray();

            $array = EmployeeProfile::with([
                'assignedArea',
                'schedule' => function ($query) use ($year, $month) {
                    $query->with(['timeShift', 'holiday'])
                        ->whereYear('date', '=', $year)
                        ->whereMonth('date', '=', $month);
                }
            ])->whereIn('id', $employee_ids)
                ->where(function ($query) use ($user, $assigned_area) {
                    return $assigned_area['details']['code'] === "HRMO" ?
                        $query->whereNotIn('id', [$user->id, 1, 2, 3, 4, 5]) :
                        $query->where('id', '!=', $user->id);
                })
                ->get();

            return response()->json([
                'data' => ScheduleResource::collection($array),
                'dates' => $dates_with_day,
                'time_shift' => TimeShiftResource::collection(TimeShift::all()),
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
                if (empty ($value)) {
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
            $employees = $cleanData['employee'];
            $selected_date = $cleanData['selected_date'];   // Selected Date;

            $schedule = null;
            $employee_schedules = null;
            $all_employee_schedules = new Collection();

            if ($selected_date === "") {
                foreach ($employees as $employee) {
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
                }
            } else {
                // Delete existing data for the selected dates and time shifts
                foreach ($employees as $employee) {
                    $schedule = EmployeeSchedule::where('employee_profile_id', $employee)->get();
                    foreach ($schedule as $value) {
                        $value->forceDelete();
                    }
                }

                // Save new data
                foreach ($selected_date as $selectedDate) {
                    $timeShiftId = $selectedDate['time_shift_id'];

                    foreach ($selectedDate['date'] as $date) {
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

                        //ALL COMMENT STILL NOT DONE
                        // $is24Hrs = Helpers::checkIs24PrevNextSchedule($data, $employees, $selectedDate);

                        // if ($is24Hrs['result'] !== 'No Schedule') {
                        //     return response()->json([$is24Hrs['result']], Response::HTTP_FOUND);
                        // }

                        // if ($this->hasOverlappingSchedule($selectedDate['time_shift_id'], $date, $employees)) {
                        //     return response()->json(['message' => 'Overlap with existing schedule'], Response::HTTP_FOUND);
                        // }

                        // Attach employees to the schedule
                        $schedule->employee()->attach($employees);
                        // $all_employee_schedules->push(new ScheduleResource($schedule));
                    }
                }
            }

            // Helpers::registerSystemLogs($request, $schedule['id'], true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                // 'data' =>  $all_employee_schedules,
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

            // $schedule = new Schedule();

            return response()->json([
                'data' => new EmployeeScheduleResource($data),
                'holiday' => Holiday::all(),
                // 'week_end' => $schedule->countWeekEnd($year, $month)
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
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
                if (empty ($value)) {
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

            // $is24Hrs = Helpers::checkIs24PrevNextSchedule($schedule, $data->employee_profile_id, $cleanData['date']);

            // if ($is24Hrs['result'] !== 'No Schedule') {
            //     return response()->json([$is24Hrs['result']], Response::HTTP_FOUND);
            // }

            // if ($this->hasOverlappingSchedule($cleanData['time_shift_id'], $cleanData['date'], $data['employee_profile_id'])) {
            //     return response()->json(['message' => 'Overlap with existing schedule'], Response::HTTP_FOUND);
            // }

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
            $myEmployees = $user->areaEmployee($assigned_area);
            $supervisors = $user->sectorHeads();

            $employees = [...$myEmployees, ...$supervisors];
            $employee_ids = collect($employees)->pluck('id')->toArray();


            $sql = EmployeeProfile::where(function ($query) use ($assigned_area) {
                $query->whereHas('schedule', function ($innerQuery) use ($assigned_area) {
                    $innerQuery->with(['timeShift', 'holiday']);
                });
            })->whereIn('id', $employee_ids)
                ->where(function ($query) use ($user, $assigned_area) {
                    return $assigned_area['details']['code'] === "HRMO" ?
                        $query->whereNotIn('id', [$user->id, 1, 2, 3, 4, 5]) :
                        $query->where('id', '!=', $user->id);
                })
                ->with(['personalInformation', 'assignedArea', 'schedule.timeShift'])->get();

            $employee = ScheduleResource::collection($sql);

            $approving_officer = Helpers::ScheduleApprovingOfficer($assigned_area, $user);
            $head_officer = EmployeeProfile::where('id', $approving_officer['approving_officer'])->first();
            $holiday = Holiday::all();

            $options = new Options();
            $options->set('isPhpEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->getOptions()->setChroot([base_path() . '/public/storage']);
            $html = view('generate_schedule/section-schedule', compact('employee', 'holiday', 'month', 'year', 'dates', 'user', 'head_officer'))->render();
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

    public function employee(Request $request)
    {
        try {
            $employees = [];
            $user = $request->user;
            $assigned_area = $user->assignedArea->findDetails();

            //Array
            // Fetch employees from employeeAreaList
            $myEmployees = $user->employeeAreaList($assigned_area);

            // Fetch supervisors
            $supervisors = $user->sectorHeads();

            // Fetch head employee ID using employeeHead function
            $employee_head = $user->employeeHead($assigned_area);

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
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findSchedule(Request $request)
    {
        try {

            $user = $request->user->id;
            $sql = EmployeeSchedule::where('employee_profile_id', $user)
                ->whereHas('schedule', function ($query) use ($request) {
                    $query->where('date', $request->date);
                })->get();

            $schedule = [];
            foreach ($sql as $value) {
                $schedule[] = [
                    'id' => $value->schedule->timeShift->id,
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
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeSchedule(Request $request)
    {
        try {

            $user = $request->user->id;
            $sql = EmployeeSchedule::where('employee_profile_id', $user)
                ->whereHas('schedule', function ($query) use ($request) {
                    $query->where('date', $request->date);
                })->get();

            $schedule = [];
            foreach ($sql as $value) {
                $schedule[] = [
                    'id' => $value->schedule->timeShift->id,
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
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function hasOverlappingSchedule($timeShiftId, $date, $employees)
    {
        foreach ($employees as $employee) {
            $existingSchedules = EmployeeProfile::find($employee)->schedule()->where('date', $date)->get();

            foreach ($existingSchedules as $existingSchedule) {
                if ($this->checkOverlap($timeShiftId, $existingSchedule->timeShift)) {
                    return true; // Overlapping schedule found
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