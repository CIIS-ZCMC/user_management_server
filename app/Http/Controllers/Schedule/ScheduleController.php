<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Resources\EmployeeScheduleResource;
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
            $employees      = [];
            $user           = $request->user;
            $month          = $request->month;   // Replace with the desired month (1 to 12)
            $year           = $request->year;     // Replace with the desired year
            $assigned_area  = $user->assignedArea->findDetails();
            $dates_with_day = Helpers::getDatesInMonth($year, $month, "Days of Week");

            //Array
            $myEmployees = $user->areaEmployee($assigned_area);
            $supervisors = $user->sectorHeads();

            $employees = [ ...$myEmployees,...$supervisors];
            $employee_ids = collect($employees)->pluck('id')->toArray();

            $array = EmployeeProfile::with(['assignedArea',
                                'schedule' => function ($query) use ($year, $month) {
                                        $query->with(['timeShift', 'holiday'])
                                            ->whereYear('date', '=', $year)
                                            ->whereMonth('date', '=', $month)
                                            ->where('employee_profile_schedule.deleted_at', '=', null );
                                }])->whereIn('id', $employee_ids)
                                // ->where('id', '!=', $user->id)
                                ->get();
                    
            $data = [];
            foreach ($array as $value) {
                $data[] = [
                    'id' => $value['id'],
                    'name' => $value->name(),
                    'employee_id' => $value['employee_id'],
                    'biometric_id' => $value['biometric_id'],
                    'assigned_area' => $value->assignedArea,
                    'schedule' => $value['schedule'],
                ];
            }

            return response()->json([
                'data' => $data,
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

            $is_weekend     = 0;
            $data           = null;
            $user           = $request->user;
            $employees      = $cleanData['employee'];
            $selected_date  = $cleanData['selected_date'];   // Selected Date;

            foreach ($selected_date as $value) {
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

                // $is24Hrs = Helpers::checkIs24PrevNextSchedule($data, $employee, $selected_date);

                // if ($is24Hrs['result'] !== 'No Schedule') {
                //     return response()->json([$is24Hrs['result']], Response::HTTP_FOUND);
                // }

                foreach ($employees as $employee) {
                    
                    if ($this->hasOverlappingSchedule($value['time_shift_id'], $value['date'], $employee['employee_id'])) {
                        return response()->json(['message' => 'Overlap with existing schedule'], Response::HTTP_FOUND);
                    }
                    
                    $existing_employee_ids = EmployeeProfile::where('id', $employee['employee_id'])->pluck('id');

                    foreach ($existing_employee_ids as $employee_id) {
                        $check_employee_schedules = EmployeeSchedule::where('employee_profile_id', $employee_id)
                                                                    ->where('schedule_id', $data->id)
                                                                    ->where('deleted_at', null)
                                                                    ->first();

                        if ($check_employee_schedules !== null) {
                            // Schedule already exists for this employee, update the schedule ID
                            $check_employee_schedules->schedule_id = $schedule->id;
                            $check_employee_schedules->update();
                        } else {
                            // No schedule exists for this employee, attach the employee to the schedule
                            $data->employee()->attach($employee_id);
                        }
                        
                        $employee_schedule = $data->employee()->where('employee_profile_id', $employee_id)->first()->id;
                        Helpers::registerEmployeeScheduleLogs($employee_schedule, $user->id, 'Store');
                    }
                }
            }

            Helpers::registerSystemLogs($request, $data['id'], true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
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

                $schedule->time_shift_id    = $cleanData['time_shift_id'];
                $schedule->is_weekend       = $is_weekend;
                $schedule->date             = $cleanData['date'];
                $schedule->save();
            }

            // $is24Hrs = Helpers::checkIs24PrevNextSchedule($schedule, $data->employee_profile_id, $cleanData['date']);

            // if ($is24Hrs['result'] !== 'No Schedule') {
            //     return response()->json([$is24Hrs['result']], Response::HTTP_FOUND);
            // }

            if ($this->hasOverlappingSchedule($cleanData['time_shift_id'], $cleanData['date'], $data['employee_profile_id'])) {
                return response()->json(['message' => 'Overlap with existing schedule'], Response::HTTP_FOUND);
            }

            $data->schedule_id = $schedule->id;
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
            $user           = $request->user;
            $assigned_area  = $user->assignedArea->findDetails();

            $month  = $request->month;  // Replace with the desired month (1 to 12)
            $year   = $request->year;   // Replace with the desired year

            $dates = Helpers::getDatesInMonth($year, Carbon::parse($month)->month, "");
            
            //Array
            $myEmployees = $user->areaEmployee($assigned_area);
            $supervisors = $user->sectorHeads();

            $employees = [ ...$myEmployees,...$supervisors];
            $employee_ids = collect($employees)->pluck('id')->toArray();

            
            $employee = EmployeeProfile::where(function ($query) use ($assigned_area) {
                $query->whereHas('schedule', function ($innerQuery) use ($assigned_area) {
                    $innerQuery->with(['timeShift', 'holiday'])->where('employee_profile_schedule.deleted_at', '=', null );
                });
            })->whereIn('id', $employee_ids)
            ->with(['personalInformation', 'assignedArea', 'schedule.timeShift'])->get();

            $approving_officer  = Helpers::ScheduleApprovingOfficer($assigned_area, $user);
            $head_officer       = EmployeeProfile::where('id', $approving_officer['approving_officer'])->first();
            $holiday            = Holiday::all();

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