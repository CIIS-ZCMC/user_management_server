<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Requests\ScheduleRequest;
use App\Http\Resources\EmployeeScheduleResource;
use App\Http\Resources\EmployeeScheduleResource2;
use App\Http\Resources\HolidayResource;
use App\Http\Resources\ScheduleResource;
use App\Models\EmployeeProfile;
use App\Helpers\Helpers;

use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\MonthlyWorkHours;
use App\Models\Schedule;
use App\Models\TimeShift;
use Carbon\Carbon;
use Illuminate\Http\Response;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            $month = $request->month;
            $year = $request->year;
            $assigned_area = $user->assignedArea->findDetails();

            // Get page and per_page parameters from the request
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);

            $isSpecialUser = $user->employee_id === "1918091351" || $assigned_area['details']['code'] === 'HRMO';

            $query = EmployeeProfile::with([
                'personalInformation',
                'schedule' => function ($query) use ($year, $month) {
                    $query->with(['timeShift', 'holiday'])
                        ->whereYear('date', '=', $year)
                        ->whereMonth('date', '=', $month);
                }
            ])->whereNull('deactivated_at')
                ->where('id', '!=', 1);

            if (!$isSpecialUser) {
                $myEmployees = $user->myEmployees($assigned_area, $user);
                $employee_ids = collect($myEmployees)->pluck('id')->toArray();
                $query->whereIn('id', $employee_ids);
            }

            // $data = $query->get();   
            $query->select('employee_profiles.*')
                ->addSelect([
                    'last_name' => function ($subquery) {
                        $subquery->select('last_name')
                            ->from('personal_informations')
                            ->whereColumn('personal_informations.id', 'employee_profiles.personal_information_id')
                            ->limit(1);
                    }
                ])
                ->orderBy('last_name');

            $data = $query->paginate($perPage, ['*'], 'page', $page);

            // $employee_ids = isset($employee_ids) ? $employee_ids : collect($data)->pluck('id')->toArray(); // Ensure $employee_ids is defined
            $employee_ids = isset($employee_ids) ? $employee_ids : collect($data->items())->pluck('id')->toArray(); // Ensure $employee_ids is defined
            $dates_with_day = Helpers::getDatesInMonth($year, $month, "Days of Week", true, $employee_ids);

            // Calculate total working hours for each employee
            $data->getCollection()->transform(function ($employee) {
                $employee->total_working_hours = $employee->schedule->sum(function ($schedule) {
                    return $schedule->timeShift->total_hours ?? 0;
                });
                return $employee;
            });

            // $data->each(function ($employee) {
            //     $employee->total_working_hours = $employee->schedule->sum(function ($schedule) {
            //         return $schedule->timeShift->total_hours ?? 0;
            //     });
            // });

            return response()->json([
                'data' => ScheduleResource::collection($data),
                'dates' => $dates_with_day,
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                ],
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API For Personal Calendar
     */
    public function create(Request $request)
    {
        try {
            $user = $request->user;
            $model = EmployeeSchedule::with(['employee', 'schedule.timeShift'])
                ->where('employee_profile_id', $user->id)
                ->get();

            return response()->json([
                'data' => new EmployeeScheduleResource($model),
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

            $employee = $cleanData['employee'] ?? null;
            $selected_date = $cleanData['selected_date'] ?? null;

            if ($employee !== null && $selected_date === "") {
                // Ensure $employee is a valid employee ID
                $existing_employee_ids = EmployeeProfile::where('id', $employee)->pluck('id');

                foreach ($existing_employee_ids as $employee_id) {
                    // Retrieve and delete all schedules for the employee
                    $employee_schedules = EmployeeSchedule::where('employee_profile_id', $employee_id)->get();

                    foreach ($employee_schedules as $schedule) {
                        $schedule->delete();
                    }
                }
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
            $data = EmployeeSchedule::where('employee_profile_id', $id)
                ->with(['schedule'])
                ->get();

            // Calculate total working hours for each employee
            $data->each(function ($employeeSchedule) {
                $employeeSchedule->total_working_hours = $employeeSchedule->schedule->timeShift->total_hours ?? 0;
            });

            if ($data->isEmpty()) {
                $data = EmployeeProfile::find($id);
                $MWH = MonthlyWorkHours::where('employment_type_id', $data->employment_type_id)->where('month_year', Carbon::now()->format('m-Y'))->first();

                return response()->json([
                    'data' => null,
                    'updated' => [
                        'total_working_hours' => 0,
                        'monthly_working_hours' => $MWH->work_hours
                    ],
                    'holiday' => Holiday::all(),
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => new EmployeeScheduleResource($data),
                'updated' => new EmployeeScheduleResource2($data),
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

    /**
     * Generate Employee Schedule (Specific Month).
     */
    public function generate(Request $request)
    {
        try {
            $sector = $request->sector;
            $area_id = $request->area_id;
            $date = Carbon::parse($request->date)->startOfMonth();

            if ($sector === 0 && $area_id === null) {
                return response()->json(['message' => 'Please Select Area'], 200);
            }

            $ids = EmployeeProfile::whereHas('assignedArea', function ($query) use ($area_id, $sector) {
                $query->where($sector . '_id', $area_id);
            })->pluck('id');


            // Non Permanent Part-time employee
            $nonPermanentEmployees = EmployeeProfile::whereNot('employment_type_id', 2)
                ->where('shifting', 0)
                ->whereIn('id', $ids)
                ->get();
            $this->generateAndAssignSchedules($nonPermanentEmployees, 1, 'AM', $date);

            // Generate and assign schedules for permanent part-time employees (employment_type_id = 2)
            $permanentEmployees = EmployeeProfile::where('employment_type_id', 2)
                ->where('shifting', 0)
                ->whereIn('id', $ids)
                ->get();
            $this->generateAndAssignSchedules($permanentEmployees, 2, 'AM', $date);

            // Helpers::registerSystemLogs($request, $id, true, 'Success in delete ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json(['message' => 'Successfully generated schedule for the month of ' . $date->format('F')], Response::HTTP_OK);
        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function remove(Request $request)
    {
        try {
            $date = $request->date;
            $sector = $request->sector;
            $area_id = $request->area_id;

            // Retrieve schedule IDs for the given date range
            $to_delete_ids = Schedule::where('date', 'like', $date . '%')->pluck('id');

            if ($sector === 0 && $area_id === null) {
                EmployeeSchedule::whereIn('schedule_id', $to_delete_ids)->delete();
                return response()->json(['message' => 'Employee schedules deleted successfully'], 200);
            }

            // Narrower delete scope based on sector and area_id
            EmployeeSchedule::whereHas('employee.assignedArea', function ($query) use ($area_id, $sector) {
                $query->where($sector . '_id', $area_id);
            })->whereIn('schedule_id', $to_delete_ids)->delete();


            // Helpers::registerSystemLogs($request, $id, true, 'Success in delete ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json(['message' => 'Employee schedules deleted successfully'], 200);
        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function upload(Request $request)
    {
        try {
            // Validate the file
            $request->validate([
                'csv_file' => 'required|mimes:csv,txt|max:2048',
            ]);

            // Check if the file is valid
            if ($request->file('csv_file')->isValid()) {
                $file = $request->file('csv_file');
                $filePath = $file->storeAs('csv', time() . '.' . $file->getClientOriginalExtension());

                // Read and process the CSV file
                $csvData = array_map('str_getcsv', file(storage_path('app/' . $filePath)));
                $header = array_shift($csvData);

                foreach ($csvData as $row) {
                    $employee_id = $row[0];
                    $month = $row[2];
                    $year = $row[3];

                    $employee = EmployeeProfile::where('employee_id', $employee_id)->first();

                    if (!$employee) {
                        // Handle the case where employee is not found (optional)
                        continue;
                    }

                    // Loop through each day of the month
                    for ($i = 4; $i < count($row); $i++) {
                        $shift = $row[$i];
                        switch ($shift) {
                            case '8':
                                $time_shift = TimeShift::where('first_in', '08:00:00')->where('first_out', '16:00:00')->first()->id;
                                break;
                            case '6':
                                $time_shift = TimeShift::where('first_in', '06:00:00')->where('first_out', '14:00:00')->first()->id;
                                break;
                            case '2':
                                $time_shift = TimeShift::where('first_in', '14:00:00')->where('first_out', '22:00:00')->first()->id;
                                break;
                            case '10':
                                $time_shift = TimeShift::where('first_in', '22:00:00')->where('first_out', '06:00:00')->first()->id;
                                break;
                            case 'H':
                                $time_shift = null;
                                break;
                            case '✓':
                                $time_shift = null;
                                break;
                            default:
                                $time_shift = TimeShift::where('first_in', '08:00:00')->where('second_out', '17:00:00')->first()->id;
                                break;
                        }

                        if ($time_shift !== null) {
                            $month_parse = Carbon::parse("first day of $month $year")->format('m');
                            $day = $i - 3; // Because the first 4 columns are Lastname, Firstname, Month, Year
                            $date = Carbon::create($year, $month_parse, $day)->format('Y-m-d');

                            $isweekend = (Carbon::parse($date))->isWeekend();

                            // Create or get the schedule
                            $schedule = Schedule::firstOrCreate(
                                [
                                    'date' => $date,
                                    'time_shift_id' => $time_shift,
                                    'is_weekend' => $isweekend ? 1 : 0
                                ],
                                [
                                    'date' => $date,
                                    'time_shift_id' => $time_shift,
                                    'is_weekend' => $isweekend ? 1 : 0
                                ],
                            );

                            // Create or update employee schedule
                            EmployeeSchedule::updateOrCreate(
                                [
                                    'employee_profile_id' => $employee->id,
                                    'schedule_id' => $schedule->id,
                                ],
                                [
                                    'employee_profile_id' => $employee->id,
                                    'schedule_id' => $schedule->id,
                                ]
                            );
                        }
                    }
                }

                return response()->json(['message' => 'CSV data uploaded successfully!'], Response::HTTP_OK);
            }
            return response()->json(['message' => 'Invalid file uploaded.'], Response::HTTP_NOT_ACCEPTABLE);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function import(Request $request)
    {
        try {
            // Validate the file
            $request->validate([
                'csv_file' => 'required|mimes:csv,txt|max:2048',
            ]);

            // Check if the file is valid
            if ($request->file('csv_file')->isValid()) {
                $file = $request->file('csv_file');
                $filePath = $file->storeAs('csv', time() . '.' . $file->getClientOriginalExtension());

                try {
                    // Read and process the CSV file
                    $csvData = array_map('str_getcsv', file(storage_path('app/' . $filePath)));
                    $header = array_shift($csvData);
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Error reading CSV data'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $errors = [];
                foreach ($csvData as $row) {
                    $personal_information_id = $row[0];
                    $date = $row[1];
                    $shift = $row[2];

                    //Find employee
                    $employee = EmployeeProfile::where('personal_information_id', $personal_information_id)->first();

                    if (!$employee) {
                        $errors[] = "Employee with ID {$personal_information_id} not found.";
                        continue;
                    }

                    // Handle Dayoff case
                    if (strtolower($shift) === 'dayoff') {
                        // Find and delete employee schedule for this date if exists
                        $employee_schedule = EmployeeSchedule::where('employee_profile_id', $employee->id)
                            ->whereHas('schedule', function ($query) use ($date) {
                                $query->where('date', '=', $date);
                            })->first();

                        if ($employee_schedule) {
                            $employee_schedule->delete();
                        }
                        continue; // Skip to the next row since it's a Dayoff
                    }

                    // If shift is not "Dayoff", proceed to find or create schedule
                    $schedule = Schedule::firstOrCreate(
                        ['date' => $date, 'time_shift_id' => $shift],
                        ['is_weekend' => Carbon::parse($date)->isWeekend() ? 1 : 0]
                    );

                    // Find if the employee already has a schedule on this date
                    $employee_schedule = EmployeeSchedule::with('schedule')->where('employee_profile_id', $employee->id)
                        ->whereHas('schedule', function ($query) use ($date) {
                            $query->where('date', '=', $date);
                        })->delete();

                    // Create a new employee schedule
                    EmployeeSchedule::create([
                        'employee_profile_id' => $employee->id,
                        'schedule_id' => $schedule->id,
                    ]);
                }

                if (!empty($errors)) {
                    return response()->json(['message' => 'CSV import completed with some errors', 'errors' => $errors], Response::HTTP_PARTIAL_CONTENT);
                }

                return response()->json(['message' => 'CSV data imported successfully!'], Response::HTTP_OK);
            }

            return response()->json(['message' => 'Invalid file uploaded.'], Response::HTTP_NOT_ACCEPTABLE);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Invalid file format or size'], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function generateAndAssignSchedules($employees, $employmentTypeId, $shiftType, $date)
    {
        $schedules = Helpers::generateSchedule($date, $employmentTypeId, $shiftType);

        foreach ($employees as $employee) {
            foreach ($schedules as $schedule) {
                // Check if EmployeeSchedule already exists for this employee and schedule
                $exists = EmployeeSchedule::where('employee_profile_id', $employee->id)
                    ->where('schedule_id', $schedule->id)
                    ->exists();

                if (!$exists) {
                    EmployeeSchedule::create([
                        'employee_profile_id' => $employee->id,
                        'schedule_id' => $schedule->id
                    ]);
                }
            }
        }
    }


}
