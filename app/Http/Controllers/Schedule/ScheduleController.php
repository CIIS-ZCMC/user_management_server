<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Resources\EmployeeScheduleResource;
use App\Http\Resources\HolidayResource;
use App\Models\AssignArea;
use App\Models\Department;
use App\Models\Division;
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
     * Generate PDF file of schedule
     */
    public function generate(Request $request)
    {
        try {
            $user = $request->user;
            $assigned_area = $user->assignedArea->findDetails();

            $month = $request->month;  // Replace with the desired month (1 to 12)
            $year = $request->year;   // Replace with the desired year

            $get_dates = Helpers::getDatesInMonth($year, Carbon::parse($month)->month, "");

            $dates = array_map(function ($item) {
                return $item['date'];
            }, $get_dates);

            $employees = [$request->employees];
            $employee_ids = explode(',', $employees[0]);

            $sql = EmployeeProfile::whereIn('id', $employee_ids)
                ->with([
                    'schedule' => function ($query) {
                        $query->with(['timeShift', 'holiday']);
                    },
                    'personalInformation',
                    'assignedArea',
                    'schedule.timeShift'
                ])->get();

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
            // $position = $user->position();

            // if (!$position) {
            //     return response()->json(['message' => "You don't have authorization as a supervisor of area."], Response::HTTP_FORBIDDEN);
            // }

            $my_area = $user->assignedArea->findDetails();

            if ($my_area['details']->code === 'HRMO' || $user->employee_id === '1918091351') {
                return response()->json(['data' => $this->areas(), 'message' => "HRMO."], Response::HTTP_OK);
            }

            $areas = [];
            switch ($my_area['sector']) {
                case "Division":
                    $areas[] = ['id' => $my_area['details']->id, 'name' => $my_area['details']->name, 'code' => $my_area['details']->code, 'sector' => $my_area['sector']];
                    $deparmtents = Department::where('division_id', $my_area['details']->id)->get();

                    foreach ($deparmtents as $department) {
                        $areas[] = ['id' => $department->id, 'name' => $department->name, 'code' => $department->code, 'sector' => 'Department'];

                        $sections = Section::where('department_id', $department->id)->get();
                        foreach ($sections as $section) {
                            $areas[] = ['id' => $section->id, 'name' => $section->name, 'code' => $section->code, 'sector' => 'Section'];

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $areas[] = ['id' => $unit->id, 'name' => $unit->name, 'code' => $unit->code, 'sector' => 'Unit'];
                            }
                        }
                    }

                    $sections = Section::where('division_id', $my_area['details']->id)->get();
                    foreach ($sections as $section) {
                        $areas[] = ['id' => $section->id, 'name' => $section->name, 'code' => $section->code, 'sector' => 'Section'];

                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $areas[] = ['id' => $unit->id, 'name' => $unit->name, 'code' => $unit->code, 'sector' => 'Unit'];
                        }
                    }
                    break;
                case "Department":
                    $areas[] = ['id' => $my_area['details']->id, 'name' => $my_area['details']->name, 'code' => $my_area['details']->code, 'sector' => $my_area['sector']];
                    $sections = Section::where('department_id', $my_area['details']->id)->get();

                    foreach ($sections as $section) {
                        $areas[] = ['id' => $section->id, 'name' => $section->name, 'code' => $section->code, 'sector' => 'Section'];

                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $areas[] = ['id' => $unit->id, 'name' => $unit->name, 'code' => $unit->code, 'sector' => 'Unit'];
                        }
                    }
                    break;

                case "Section":
                    $areas[] = ['id' => $my_area['details']->id, 'name' => $my_area['details']->name, 'code' => $my_area['details']->code, 'sector' => $my_area['sector']];

                    $units = Unit::where('section_id', $my_area['details']->id)->get();
                    foreach ($units as $unit) {
                        $areas[] = ['id' => $unit->id, 'name' => $unit->name, 'code' => $unit->code, 'sector' => 'Unit'];
                    }
                    break;

                case "Unit":
                    $areas[] = ['id' => $my_area['details']->id, 'name' => $my_area['details']->name, 'code' => $my_area['details']->code, 'sector' => $my_area['sector']];
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


    public function FilterByAreaAndDate(Request $request)
    {
        try {
            $user = $request->user;

            $area_id = $request->area_id;
            $area_sector = strtolower($request->area_sector);
            $year = Carbon::parse($request->date)->year;
            $month = Carbon::parse($request->date)->month;



            $data = EmployeeProfile::with([
                'assignedArea',
                'schedule' => function ($innerQuery) use ($year, $month) {
                    $innerQuery->with(['timeShift', 'holiday'])
                        ->whereYear('date', '=', $year)
                        ->whereMonth('date', '=', $month);
                }
            ])->whereHas('assignedArea', function ($query) use ($area_id, $area_sector) {
                $query->where($area_sector . '_id', $area_id);
            })->whereInget();


            return response()->json(['data' => ScheduleResource::collection($data)], Response::HTTP_OK);
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

    private function areas()
    {
        try {
            $divisions = Division::all();
            $departments = Department::all();
            $sections = Section::all();
            $units = Unit::all();

            $all_areas = [];

            foreach ($divisions as $division) {
                $area = [
                    'area' => $division->id,
                    'name' => $division->name,
                    'sector' => 'division'
                ];
                $all_areas[] = $area;
            }

            foreach ($departments as $department) {
                $area = [
                    'area' => $department->id,
                    'name' => $department->name,
                    'sector' => 'department'
                ];
                $all_areas[] = $area;
            }

            foreach ($sections as $section) {
                $area = [
                    'area' => $section->id,
                    'name' => $section->name,
                    'sector' => 'section'
                ];
                $all_areas[] = $area;
            }

            foreach ($units as $unit) {
                $area = [
                    'area' => $unit->id,
                    'name' => $unit->name,
                    'sector' => 'unit'
                ];
                $all_areas[] = $area;
            }

            return $all_areas;
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'assignPlantillaToAreas', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
