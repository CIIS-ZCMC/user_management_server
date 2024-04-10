<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Helpers\Helpers;
use App\Models\OvertimeApplication;
use App\Http\Controllers\Controller;
use App\Models\AssignArea;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\OvtApplicationActivity;
use App\Models\OvtApplicationDatetime;
use App\Models\OvtApplicationEmployee;
use App\Models\OvtApplicationLog;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Random\Engine\Secure;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class OvertimeApplicationController extends Controller
{
    private $CONTROLLER_NAME = 'OvertimeApplicationController';

    public function index()
    {
        try {
            $overtime_applications = [];
            $overtime_applications = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'activities'])->get();
            if ($overtime_applications->isNotEmpty()) {
                $overtime_applications_result = $overtime_applications->map(function ($overtime_application) {
                    $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                    $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                    $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                    $chief_name = null;
                    $chief_position = null;
                    $chief_code = null;
                    $head_name = null;
                    $head_position = null;
                    $head_code = null;
                    $supervisor_name = null;
                    $supervisor_position = null;
                    $supervisor_code = null;
                    if ($division) {
                        $division_name = Division::with('chief.personalInformation')->find($division);

                        if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                            $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                            $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                            $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                        }
                    }
                    if ($department) {
                        $department_name = Department::with('head.personalInformation')->find($department);
                        if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                            $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                            $head_position = $department_name->head->assignedArea->designation->name ?? null;
                            $head_code = $department_name->head->assignedArea->designation->code ?? null;
                        }
                    }
                    if ($section) {
                        $section_name = Section::with('supervisor.personalInformation')->find($section);
                        if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                            $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                            $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                            $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                        }
                    }
                    $ovt_application_activities = OvtApplicationActivity::where('overtime_application_id', $overtime_application->id)->get();
                    if ($ovt_application_activities->isNotEmpty()) {
                        $total_in_minutes = 0;
                        $dates = [];

                        foreach ($ovt_application_activities as $activity) {
                            $ovt_application_date_times = OvtApplicationDatetime::where('ovt_application_activity_id', $activity->id)->get();

                            foreach ($ovt_application_date_times as $ovt_application_date_time) {
                                $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                                $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                                $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                                $total_in_minutes += $timeInMinutes;

                                $dates[] = $ovt_application_date_time->date;
                            }
                        }

                        $total_days = count(array_unique($dates));

                        $total_hours_credit = number_format($total_in_minutes / 60, 1);
                        $hours = floor($total_hours_credit);
                        $minutes = round(($total_hours_credit - $hours) * 60);

                        if ($minutes > 0) {
                            $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                        } else {
                            $result = sprintf('%d hours', $hours, $total_days);
                        }
                    } else {
                        $total_in_minutes = 0;
                        $dates = [];
                        $ovt_application_date_times = OvtApplicationDatetime::where('overtime_application_id', $overtime_application->id)->get();
                        foreach ($ovt_application_date_times as $ovt_application_date_time) {
                            $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                            $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                            $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                            $total_in_minutes += $timeInMinutes;

                            $dates[] = $ovt_application_date_time->date;
                        }

                        $total_days = count(array_unique($dates));

                        $total_hours_credit = number_format($total_in_minutes / 60, 1);
                        $hours = floor($total_hours_credit);
                        $minutes = round(($total_hours_credit - $hours) * 60);

                        if ($minutes > 0) {
                            $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                        } else {
                            $result = sprintf('%d hours', $hours, $total_days);
                        }
                    }

                    $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $overtime_application->id,
                        'total_days' => $total_days,
                        'total_hours' => $result,
                        'reason' => $overtime_application->reason,
                        'remarks' => $overtime_application->remarks,
                        'purpose' => $overtime_application->purpose,
                        'status' => $overtime_application->status,
                        'overtime_letter' => $overtime_application->overtime_letter_of_request,
                        'employee_id' => $overtime_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}",
                        'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $overtime_application->created_at,
                        'division_head' => $chief_name,
                        'division_head_position' => $chief_position,
                        'division_head_code' => $chief_code,
                        'department_head' => $head_name,
                        'department_head_position' => $head_position,
                        'department_head_code' => $head_code,
                        'section_head' => $supervisor_name,
                        'section_head_position' => $supervisor_position,
                        'section_head_code' => $supervisor_code,
                        'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                        'date' => $overtime_application->date,
                        'time' => $overtime_application->time,
                        'logs' => $logsData->map(function ($log) {
                            $process_name = $log->action;
                            $action = "";
                            $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                            if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                                $action =  $process_name . ' by ' . 'Division Head';
                            } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                                $action =  $process_name . ' by ' . 'Supervisor';
                            } else {
                                $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                            }
                            $date = $log->date;
                            $formatted_date = Carbon::parse($date)->format('M d,Y');
                            return [
                                'id' => $log->id,
                                'overtime_application_id' => $log->overtime_application_id,
                                'action_by' => "{$first_name} {$last_name}",
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'activities' => $activitiesData->map(function ($activity) {
                            return [
                                'id' => $activity->id,
                                'overtime_application_id' => $activity->overtime_application_id,
                                'name' => $activity->name,
                                'quantity' => $activity->quantity,
                                'man_hour' => $activity->man_hour,
                                'period_covered' => $activity->period_covered,
                                'dates' => $activity->dates->map(function ($date) {
                                    return [
                                        'id' => $date->id,
                                        'ovt_activity_id' => $date->ovt_application_activity_id,
                                        'time_from' => $date->time_from,
                                        'time_to' => $date->time_to,
                                        'date' => $date->date,
                                        'employees' => $date->employees->map(function ($employee) {
                                            $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                            $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                            return [
                                                'id' => $employee->id,
                                                'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                                'employee_id' => $employee->employee_profile_id,
                                                'employee_name' => "{$first_name} {$last_name}",
                                                'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                            ];
                                        }),

                                    ];
                                }),
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
                            return [
                                'id' => $date->id,
                                'ovt_activity_id' => $date->ovt_application_activity_id,
                                'time_from' => $date->time_from,
                                'time_to' => $date->time_to,
                                'date' => $date->date,
                                'employees' => $date->employees->map(function ($employee) {
                                    $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                    $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                    return [
                                        'id' => $employee->id,
                                        'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                        'employee_id' => $employee->employee_profile_id,
                                        'employee_name' => "{$first_name} {$last_name}",
                                        'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                    ];
                                }),
                            ];
                        }),

                    ];
                });
                return response()->json(['data' => $overtime_applications_result], Response::HTTP_OK);
            } else {
                return response()->json(['data' => $overtime_applications, 'message' => 'No records available'], Response::HTTP_OK);
            }
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUserOvertimeApplication(Request $request)
    {
        try {
            $user = $request->user;
            $overtime_applications = [];
            $overtime_applications = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'activities'])
                ->where('employee_profile_id', $user->id)->get();
            if ($overtime_applications->isNotEmpty()) {
                $overtime_applications_result = $overtime_applications->map(function ($overtime_application) {
                    $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                    $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                    $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                    $chief_name = null;
                    $chief_position = null;
                    $chief_code = null;
                    $head_name = null;
                    $head_position = null;
                    $head_code = null;
                    $supervisor_name = null;
                    $supervisor_position = null;
                    $supervisor_code = null;
                    if ($division) {
                        $division_name = Division::with('chief.personalInformation')->find($division);

                        if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                            $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                            $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                            $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                        }
                    }
                    if ($department) {
                        $department_name = Department::with('head.personalInformation')->find($department);
                        if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                            $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                            $head_position = $department_name->head->assignedArea->designation->name ?? null;
                            $head_code = $department_name->head->assignedArea->designation->code ?? null;
                        }
                    }
                    if ($section) {
                        $section_name = Section::with('supervisor.personalInformation')->find($section);
                        if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                            $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                            $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                            $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                        }
                    }
                    $ovt_application_activities = OvtApplicationActivity::where('overtime_application_id', $overtime_application->id)->get();
                    if ($ovt_application_activities->isNotEmpty()) {
                        $total_in_minutes = 0;
                        $dates = [];

                        foreach ($ovt_application_activities as $activity) {
                            $ovt_application_date_times = OvtApplicationDatetime::where('ovt_application_activity_id', $activity->id)->get();

                            foreach ($ovt_application_date_times as $ovt_application_date_time) {
                                $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                                $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                                $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                                $total_in_minutes += $timeInMinutes;

                                $dates[] = $ovt_application_date_time->date;
                            }
                        }

                        $total_days = count(array_unique($dates));

                        $total_hours_credit = number_format($total_in_minutes / 60, 1);
                        $hours = floor($total_hours_credit);
                        $minutes = round(($total_hours_credit - $hours) * 60);

                        if ($minutes > 0) {
                            $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                        } else {
                            $result = sprintf('%d hours', $hours, $total_days);
                        }
                    } else {
                        $total_in_minutes = 0;
                        $dates = [];
                        $ovt_application_date_times = OvtApplicationDatetime::where('overtime_application_id', $overtime_application->id)->get();
                        foreach ($ovt_application_date_times as $ovt_application_date_time) {
                            $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                            $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                            $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                            $total_in_minutes += $timeInMinutes;

                            $dates[] = $ovt_application_date_time->date;
                        }

                        $total_days = count(array_unique($dates));

                        $total_hours_credit = number_format($total_in_minutes / 60, 1);
                        $hours = floor($total_hours_credit);
                        $minutes = round(($total_hours_credit - $hours) * 60);

                        if ($minutes > 0) {
                            $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                        } else {
                            $result = sprintf('%d hours', $hours, $total_days);
                        }
                    }
                    $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $overtime_application->id,
                        'total_days' => $total_days,
                        'total_hours' => $result,
                        'reason' => $overtime_application->reason,
                        'remarks' => $overtime_application->remarks,
                        'purpose' => $overtime_application->purpose,
                        'status' => $overtime_application->status,
                        'overtime_letter' => $overtime_application->overtime_letter_of_request,
                        'employee_id' => $overtime_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}",
                        'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $overtime_application->created_at,
                        'division_head' => $chief_name,
                        'division_head_position' => $chief_position,
                        'division_head_code' => $chief_code,
                        'department_head' => $head_name,
                        'department_head_position' => $head_position,
                        'department_head_code' => $head_code,
                        'section_head' => $supervisor_name,
                        'section_head_position' => $supervisor_position,
                        'section_head_code' => $supervisor_code,
                        'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                        'date' => $overtime_application->date,
                        'time' => $overtime_application->time,
                        'logs' => $logsData->map(function ($log) {
                            $process_name = $log->action;
                            $action = "";
                            $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                            if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                                $action =  $process_name . ' by ' . 'Division Head';
                            } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                                $action =  $process_name . ' by ' . 'Supervisor';
                            } else {
                                $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                            }
                            $date = $log->date;
                            $formatted_date = Carbon::parse($date)->format('M d,Y');
                            return [
                                'id' => $log->id,
                                'overtime_application_id' => $log->overtime_application_id,
                                'action_by' => "{$first_name} {$last_name}",
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'activities' => $activitiesData->map(function ($activity) {
                            return [
                                'id' => $activity->id,
                                'overtime_application_id' => $activity->overtime_application_id,
                                'name' => $activity->name,
                                'quantity' => $activity->quantity,
                                'man_hour' => $activity->man_hour,
                                'period_covered' => $activity->period_covered,
                                'dates' => $activity->dates->map(function ($date) {
                                    return [
                                        'id' => $date->id,
                                        'ovt_activity_id' => $date->ovt_application_activity_id,
                                        'time_from' => $date->time_from,
                                        'time_to' => $date->time_to,
                                        'date' => $date->date,
                                        'employees' => $date->employees->map(function ($employee) {
                                            $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                            $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                            return [
                                                'id' => $employee->id,
                                                'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                                'employee_id' => $employee->employee_profile_id,
                                                'employee_name' => "{$first_name} {$last_name}",
                                                'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                            ];
                                        }),

                                    ];
                                }),
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
                            return [
                                'id' => $date->id,
                                'ovt_activity_id' => $date->ovt_application_activity_id,
                                'time_from' => $date->time_from,
                                'time_to' => $date->time_to,
                                'date' => $date->date,
                                'employees' => $date->employees->map(function ($employee) {
                                    $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                    $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                    return [
                                        'id' => $employee->id,
                                        'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                        'employee_id' => $employee->employee_profile_id,
                                        'employee_name' => "{$first_name} {$last_name}",
                                        'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                    ];
                                }),
                            ];
                        }),

                    ];
                });
                return response()->json(['data' => $overtime_applications_result], Response::HTTP_OK);
            } else {
                return response()->json(['data' => $overtime_applications, 'message' => 'No records available'], Response::HTTP_OK);
            }
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getUserOvertimeApplication', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getOvertimeApplications(Request $request)
    {
        try {
            $user = $request->user;
            $OvertimeApplication = [];
            $omcc_head_id = Division::where('code', 'OMCC')->value('chief_employee_profile_id');
            $omcc_oic_id = Division::where('code', 'OMCC')->value('oic_employee_profile_id');
            $division = AssignArea::where('employee_profile_id', $user->id)->value('division_id');
            $division_oic_Id = Division::where('id', $division)->value('chief_employee_profile_id');
            $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
            $department = AssignArea::where('employee_profile_id', $user->id)->value('department_id');
            $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
            $section = AssignArea::where('employee_profile_id', $user->id)->value('section_id');
            $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
            $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
            if ($divisionHeadId === $user->id || $division_oic_Id === $user->id) {
                $OvertimeApplication = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'activities'])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                        $query->where('division_id', $division);
                    })
                    ->where('status', 'for-approval-division-head')
                    ->orwhere('status', 'approved')
                    ->orwhere('status', 'declined')
                    ->get();
                if ($OvertimeApplication->isNotEmpty()) {
                    $overtime_applications_result = $OvertimeApplication->map(function ($overtime_application) {
                        $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                        $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                        $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                        $chief_name = null;
                        $chief_position = null;
                        $chief_code = null;
                        $head_name = null;
                        $head_position = null;
                        $head_code = null;
                        $supervisor_name = null;
                        $supervisor_position = null;
                        $supervisor_code = null;
                        if ($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                                $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                                $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($department) {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                $head_code = $department_name->head->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($section) {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                            }
                        }

                        $ovt_application_activities = OvtApplicationActivity::where('overtime_application_id', $overtime_application->id)->get();
                        if ($ovt_application_activities->isNotEmpty()) {
                            $total_in_minutes = 0;
                            $dates = [];

                            foreach ($ovt_application_activities as $activity) {
                                $ovt_application_date_times = OvtApplicationDatetime::where('ovt_application_activity_id', $activity->id)->get();

                                foreach ($ovt_application_date_times as $ovt_application_date_time) {
                                    $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                                    $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                                    $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                                    $total_in_minutes += $timeInMinutes;

                                    $dates[] = $ovt_application_date_time->date;
                                }
                            }

                            $total_days = count(array_unique($dates));

                            $total_hours_credit = number_format($total_in_minutes / 60, 1);
                            $hours = floor($total_hours_credit);
                            $minutes = round(($total_hours_credit - $hours) * 60);

                            if ($minutes > 0) {
                                $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                            } else {
                                $result = sprintf('%d hours', $hours, $total_days);
                            }
                        } else {
                            $total_in_minutes = 0;
                            $dates = [];
                            $ovt_application_date_times = OvtApplicationDatetime::where('overtime_application_id', $overtime_application->id)->get();
                            foreach ($ovt_application_date_times as $ovt_application_date_time) {
                                $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                                $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                                $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                                $total_in_minutes += $timeInMinutes;

                                $dates[] = $ovt_application_date_time->date;
                            }

                            $total_days = count(array_unique($dates));

                            $total_hours_credit = number_format($total_in_minutes / 60, 1);
                            $hours = floor($total_hours_credit);
                            $minutes = round(($total_hours_credit - $hours) * 60);

                            if ($minutes > 0) {
                                $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                            } else {
                                $result = sprintf('%d hours', $hours, $total_days);
                            }
                        }
                        $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $overtime_application->id,
                            'total_days' => $total_days,
                            'total_hours' => $result,
                            'reason' => $overtime_application->reason,
                            'remarks' => $overtime_application->remarks,
                            'purpose' => $overtime_application->purpose,
                            'status' => $overtime_application->status,
                            'overtime_letter' => $overtime_application->overtime_letter_of_request,
                            'employee_id' => $overtime_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}",
                            'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $overtime_application->created_at,
                            'division_head' => $chief_name,
                            'division_head_position' => $chief_position,
                            'division_head_code' => $chief_code,
                            'department_head' => $head_name,
                            'department_head_position' => $head_position,
                            'department_head_code' => $head_code,
                            'section_head' => $supervisor_name,
                            'section_head_position' => $supervisor_position,
                            'section_head_code' => $supervisor_code,
                            'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                            'date' => $overtime_application->date,
                            'time' => $overtime_application->time,
                            'logs' => $logsData->map(function ($log) {
                                $process_name = $log->action;
                                $action = "";
                                $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                                $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Division Head';
                                } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Supervisor';
                                } else {
                                    $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                                }

                                $date = $log->date;
                                $formatted_date = Carbon::parse($date)->format('M d,Y');
                                return [
                                    'id' => $log->id,
                                    'overtime_application_id' => $log->overtime_application_id,
                                    'action_by' => "{$first_name} {$last_name}",
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'activities' => $activitiesData->map(function ($activity) {
                                return [
                                    'id' => $activity->id,
                                    'overtime_application_id' => $activity->overtime_application_id,
                                    'name' => $activity->name,
                                    'quantity' => $activity->quantity,
                                    'man_hour' => $activity->man_hour,
                                    'period_covered' => $activity->period_covered,
                                    'dates' => $activity->dates->map(function ($date) {
                                        return [
                                            'id' => $date->id,
                                            'ovt_activity_id' => $date->ovt_application_activity_id,
                                            'time_from' => $date->time_from,
                                            'time_to' => $date->time_to,
                                            'date' => $date->date,
                                            'employees' => $date->employees->map(function ($employee) {
                                                $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                                $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                                return [
                                                    'id' => $employee->id,
                                                    'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                                    'employee_id' => $employee->employee_profile_id,
                                                    'employee_name' => "{$first_name} {$last_name}",
                                                    'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                                ];
                                            }),

                                        ];
                                    }),
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
                                return [
                                    'id' => $date->id,
                                    'ovt_activity_id' => $date->ovt_application_activity_id,
                                    'time_from' => $date->time_from,
                                    'time_to' => $date->time_to,
                                    'date' => $date->date,
                                    'employees' => $date->employees->map(function ($employee) {
                                        $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                        $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                        return [
                                            'id' => $employee->id,
                                            'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                            'employee_id' => $employee->employee_profile_id,
                                            'employee_name' => "{$first_name} {$last_name}",
                                            'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                        ];
                                    }),
                                ];
                            }),

                        ];
                    });
                    return response()->json(['data' => $overtime_applications_result]);
                } else {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }
            } else if ($omcc_head_id === $user->id || $omcc_oic_id === $user->id) {
                $OvertimeApplication = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'activities'])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                        $query->where('section_id', $section);
                    })
                    ->where('status', 'for-approval-omcc-head')
                    ->orwhere('status', 'approved')
                    ->get();
                if ($OvertimeApplication->isNotEmpty()) {
                    $overtime_applications_result = $OvertimeApplication->map(function ($overtime_application) {
                        $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                        $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                        $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                        $chief_name = null;
                        $chief_position = null;
                        $chief_code = null;
                        $head_name = null;
                        $head_position = null;
                        $head_code = null;
                        $supervisor_name = null;
                        $supervisor_position = null;
                        $supervisor_code = null;
                        if ($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                                $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                                $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($department) {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                $head_code = $department_name->head->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($section) {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                            }
                        }

                        $ovt_application_activities = OvtApplicationActivity::where('overtime_application_id', $overtime_application->id)->get();
                        if ($ovt_application_activities->isNotEmpty()) {
                            $total_in_minutes = 0;
                            $dates = [];

                            foreach ($ovt_application_activities as $activity) {
                                $ovt_application_date_times = OvtApplicationDatetime::where('ovt_application_activity_id', $activity->id)->get();

                                foreach ($ovt_application_date_times as $ovt_application_date_time) {
                                    $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                                    $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                                    $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                                    $total_in_minutes += $timeInMinutes;

                                    $dates[] = $ovt_application_date_time->date;
                                }
                            }

                            $total_days = count(array_unique($dates));

                            $total_hours_credit = number_format($total_in_minutes / 60, 1);
                            $hours = floor($total_hours_credit);
                            $minutes = round(($total_hours_credit - $hours) * 60);

                            if ($minutes > 0) {
                                $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                            } else {
                                $result = sprintf('%d hours', $hours, $total_days);
                            }
                        } else {
                            $total_in_minutes = 0;
                            $dates = [];
                            $ovt_application_date_times = OvtApplicationDatetime::where('overtime_application_id', $overtime_application->id)->get();
                            foreach ($ovt_application_date_times as $ovt_application_date_time) {
                                $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                                $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                                $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                                $total_in_minutes += $timeInMinutes;

                                $dates[] = $ovt_application_date_time->date;
                            }

                            $total_days = count(array_unique($dates));

                            $total_hours_credit = number_format($total_in_minutes / 60, 1);
                            $hours = floor($total_hours_credit);
                            $minutes = round(($total_hours_credit - $hours) * 60);

                            if ($minutes > 0) {
                                $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                            } else {
                                $result = sprintf('%d hours', $hours, $total_days);
                            }
                        }
                        $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $overtime_application->id,
                            'total_days' => $total_days,
                            'total_hours' => $result,
                            'reason' => $overtime_application->reason,
                            'remarks' => $overtime_application->remarks,
                            'purpose' => $overtime_application->purpose,
                            'status' => $overtime_application->status,
                            'overtime_letter' => $overtime_application->overtime_letter_of_request,
                            'employee_id' => $overtime_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}",
                            'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $overtime_application->created_at,
                            'division_head' => $chief_name,
                            'division_head_position' => $chief_position,
                            'division_head_code' => $chief_code,
                            'department_head' => $head_name,
                            'department_head_position' => $head_position,
                            'department_head_code' => $head_code,
                            'section_head' => $supervisor_name,
                            'section_head_position' => $supervisor_position,
                            'section_head_code' => $supervisor_code,
                            'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                            'date' => $overtime_application->date,
                            'time' => $overtime_application->time,
                            'logs' => $logsData->map(function ($log) {
                                $process_name = $log->action;
                                $action = "";
                                $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                                $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Division Head';
                                } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Supervisor';
                                } else {
                                    $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                                }

                                $date = $log->date;
                                $formatted_date = Carbon::parse($date)->format('M d,Y');
                                return [
                                    'id' => $log->id,
                                    'overtime_application_id' => $log->overtime_application_id,
                                    'action_by' => "{$first_name} {$last_name}",
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'activities' => $activitiesData->map(function ($activity) {
                                return [
                                    'id' => $activity->id,
                                    'overtime_application_id' => $activity->overtime_application_id,
                                    'name' => $activity->name,
                                    'quantity' => $activity->quantity,
                                    'man_hour' => $activity->man_hour,
                                    'period_covered' => $activity->period_covered,
                                    'dates' => $activity->dates->map(function ($date) {
                                        return [
                                            'id' => $date->id,
                                            'ovt_activity_id' => $date->ovt_application_activity_id,
                                            'time_from' => $date->time_from,
                                            'time_to' => $date->time_to,
                                            'date' => $date->date,
                                            'employees' => $date->employees->map(function ($employee) {
                                                $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                                $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                                return [
                                                    'id' => $employee->id,
                                                    'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                                    'employee_id' => $employee->employee_profile_id,
                                                    'employee_name' => "{$first_name} {$last_name}",
                                                    'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                                ];
                                            }),

                                        ];
                                    }),
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
                                return [
                                    'id' => $date->id,
                                    'ovt_activity_id' => $date->ovt_application_activity_id,
                                    'time_from' => $date->time_from,
                                    'time_to' => $date->time_to,
                                    'date' => $date->date,
                                    'employees' => $date->employees->map(function ($employee) {
                                        $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                        $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                        return [
                                            'id' => $employee->id,
                                            'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                            'employee_id' => $employee->employee_profile_id,
                                            'employee_name' => "{$first_name} {$last_name}",
                                            'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                        ];
                                    }),
                                ];
                            }),

                        ];
                    });
                    return response()->json(['data' => $overtime_applications_result]);
                } else {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }
            }

            // else if($departmentHeadId == $user->id || $training_officer_id == $user->id) {
            //     $OvertimeApplication = OvertimeApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','logs','activities'])
            //     ->whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
            //         $query->where('department_id', $department);
            //     })
            //     ->where('status', 'for-approval-section-head')
            //     ->orWhere('status', 'for-approval-division-head')
            //     ->orwhere('status', 'declined')
            //     ->get();
            // if ($OvertimeApplication->isNotEmpty()) {
            //     $overtime_applications_result = $OvertimeApplication->map(function ($overtime_application) {
            //         $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
            //         $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
            //         $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
            //         $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
            //         $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
            //         $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
            //         $chief_name = null;
            //         $chief_position = null;
            //         $chief_code = null;
            //         $head_name = null;
            //         $head_position = null;
            //         $head_code = null;
            //         $supervisor_name = null;
            //         $supervisor_position = null;
            //         $supervisor_code = null;
            //         if ($division) {
            //             $division_name = Division::with('chief.personalInformation')->find($division);

            //             if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
            //                 $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
            //                 $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
            //                 $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
            //             }
            //         }
            //         if ($department) {
            //             $department_name = Department::with('head.personalInformation')->find($department);
            //             if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
            //                 $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
            //                 $head_position = $department_name->head->assignedArea->designation->name ?? null;
            //                 $head_code = $department_name->head->assignedArea->designation->code ?? null;
            //             }
            //         }
            //         if ($section) {
            //             $section_name = Section::with('supervisor.personalInformation')->find($section);
            //             if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
            //                 $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
            //                 $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
            //                 $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
            //             }
            //         }
            //         $ovt_application_activities=OvtApplicationActivity::where('overtime_application_id',$overtime_application->id)->get();
            //         if ($ovt_application_activities->isNotEmpty()) {
            //             $total_in_minutes = 0;
            //             $dates = [];

            //             foreach ($ovt_application_activities as $activity) {
            //                 $ovt_application_date_times = OvtApplicationDatetime::where('ovt_application_activity_id', $activity->id)->get();

            //                 foreach ($ovt_application_date_times as $ovt_application_date_time) {
            //                     $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
            //                     $timeTo = Carbon::parse($ovt_application_date_time->time_to);
            //                     $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
            //                     $total_in_minutes += $timeInMinutes;

            //                     $dates[] = $ovt_application_date_time->date;
            //                 }
            //             }

            //             $total_days = count(array_unique($dates));

            //             $total_hours_credit = number_format($total_in_minutes / 60, 1);
            //             $hours = floor($total_hours_credit);
            //             $minutes = round(($total_hours_credit - $hours) * 60);

            //             if ($minutes > 0) {
            //                 $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
            //             } else {
            //                 $result = sprintf('%d hours', $hours, $total_days);
            //             }
            //         } else {
            //             $total_in_minutes = 0;
            //             $dates = [];
            //             $ovt_application_date_times = OvtApplicationDatetime::where('overtime_application_id', $overtime_application->id)->get();
            //                 foreach ($ovt_application_date_times as $ovt_application_date_time) {
            //                     $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
            //                     $timeTo = Carbon::parse($ovt_application_date_time->time_to);
            //                     $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
            //                     $total_in_minutes += $timeInMinutes;

            //                     $dates[] = $ovt_application_date_time->date;
            //                 }

            //                 $total_days = count(array_unique($dates));

            //                 $total_hours_credit = number_format($total_in_minutes / 60, 1);
            //                 $hours = floor($total_hours_credit);
            //                 $minutes = round(($total_hours_credit - $hours) * 60);

            //                 if ($minutes > 0) {
            //                     $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
            //                 } else {
            //                     $result = sprintf('%d hours', $hours, $total_days);
            //                 }

            //         }
            //         $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
            //         $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
            //         return [
            //             'id' => $overtime_application->id,
            //             'total_days'=>$total_days,
            //             'total_result'=>$result,
            //             'reason' => $overtime_application->reason,
            //             'remarks' => $overtime_application->remarks,
            //             'purpose' => $overtime_application->purpose,
            //             'status' => $overtime_application->status,
            //             'overtime_letter' => $overtime_application->overtime_letter_of_request,
            //             'employee_id' => $overtime_application->employee_profile_id,
            //             'employee_name' => "{$first_name} {$last_name}",
            //             'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
            //             'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
            //             'date_created' => $overtime_application->created_at,
            //             'division_head' => $chief_name,
            //             'division_head_position' => $chief_position,
            //             'division_head_code' => $chief_code,
            //             'department_head' => $head_name,
            //             'department_head_position' => $head_position,
            //             'department_head_code' => $head_code,
            //             'section_head' => $supervisor_name,
            //             'section_head_position' => $supervisor_position,
            //             'section_head_code' => $supervisor_code,
            //             'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
            //             'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
            //             'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
            //             'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
            //             'date' => $overtime_application->date,
            //             'time' => $overtime_application->time,
            //             'logs' => $logsData->map(function ($log) {
            //                 $process_name = $log->action;
            //                 $action = "";
            //                 $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
            //                 $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
            //                 if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
            //                     $action =  $process_name . ' by ' . 'Division Head';
            //                 } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
            //                     $action =  $process_name . ' by ' . 'Supervisor';
            //                 } else {
            //                     $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
            //                 }

            //                 $date = $log->date;
            //                 $formatted_date = Carbon::parse($date)->format('M d,Y');
            //                 return [
            //                     'id' => $log->id,
            //                     'overtime_application_id' => $log->overtime_application_id,
            //                     'action_by' => "{$first_name} {$last_name}",
            //                     'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
            //                     'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
            //                     'action' => $log->action,
            //                     'date' => $formatted_date,
            //                     'time' => $log->time,
            //                     'process' => $action
            //                 ];
            //             }),
            //             'activities' => $activitiesData->map(function ($activity) {
            //                 return [
            //                     'id' => $activity->id,
            //                     'overtime_application_id' => $activity->overtime_application_id,
            //                     'name' => $activity->name,
            //                     'quantity' => $activity->quantity,
            //                     'man_hour' => $activity->man_hour,
            //                     'period_covered' => $activity->period_covered,
            //                     'dates' => $activity->dates->map(function ($date) {
            //                         return [
            //                             'id' => $date->id,
            //                             'ovt_activity_id' => $date->ovt_application_activity_id,
            //                             'time_from' => $date->time_from,
            //                             'time_to' => $date->time_to,
            //                             'date' => $date->date,
            //                             'employees' => $date->employees->map(function ($employee) {
            //                                 $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
            //                                 $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
            //                                 return [
            //                                     'id' => $employee->id,
            //                                     'ovt_employee_id' => $employee->ovt_application_datetime_id,
            //                                     'employee_id' => $employee->employee_profile_id,
            //                                     'employee_name' => "{$first_name} {$last_name}",
            //                                     'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
            //                                 ];
            //                             }),

            //                             ];
            //                         }),
            //                     ];
            //                 }),
            //                 'dates' => $datesData->map(function ($date) {
            //                     return [
            //                                 'id' => $date->id,
            //                                 'ovt_activity_id' =>$date->ovt_application_activity_id,
            //                                 'time_from' => $date->time_from,
            //                                 'time_to' => $date->time_to,
            //                                 'date' => $date->date,
            //                                 'employees' => $date->employees->map(function ($employee) {
            //                                     $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
            //                                     $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
            //                                 return [
            //                                         'id' => $employee->id,
            //                                         'ovt_employee_id' =>$employee->ovt_application_datetime_id,
            //                                         'employee_id' => $employee->employee_profile_id,
            //                                         'employee_name' =>"{$first_name} {$last_name}",
            //                                         'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
            //                                     ];
            //                                 }),
            //                     ];
            //                 }),
            //             ];
            //             });
            //         return response()->json(['data' => $overtime_applications_result]);
            //     }
            //     else
            //     {
            //         return response()->json(['message' => 'No records available'], Response::HTTP_OK);
            //     }
            // }
            // else if($sectionHeadId == $user->id) {
            //     $overtime_applications = OvertimeApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','logs','activities'])
            //     ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
            //         $query->where('section_id', $section);
            //     })
            //     ->where('status', 'for-approval-section-head')
            //     ->orWhere('status', 'for-approval-division-head')
            //     ->orwhere('status', 'declined')
            //     ->get();
            //     if ($overtime_applications->isNotEmpty()) {
            //         $overtime_applications_result = $overtime_applications->map(function ($overtime_application) {
            //             $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
            //             $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
            //             $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
            //             $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
            //             $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
            //             $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
            //             $chief_name = null;
            //             $chief_position = null;
            //             $chief_code = null;
            //             $head_name = null;
            //             $head_position = null;
            //             $head_code = null;
            //             $supervisor_name = null;
            //             $supervisor_position = null;
            //             $supervisor_code = null;
            //             if ($division) {
            //                 $division_name = Division::with('chief.personalInformation')->find($division);

            //                 if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
            //                     $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
            //                     $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
            //                     $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
            //                 }
            //             }
            //             if ($department) {
            //                 $department_name = Department::with('head.personalInformation')->find($department);
            //                 if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
            //                     $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
            //                     $head_position = $department_name->head->assignedArea->designation->name ?? null;
            //                     $head_code = $department_name->head->assignedArea->designation->code ?? null;
            //                 }
            //             }
            //             if ($section) {
            //                 $section_name = Section::with('supervisor.personalInformation')->find($section);
            //                 if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
            //                     $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
            //                     $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
            //                     $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
            //                 }
            //             }
            //             $ovt_application_activities=OvtApplicationActivity::where('overtime_application_id',$overtime_application->id)->get();
            //             if ($ovt_application_activities->isNotEmpty()) {
            //                 $total_in_minutes = 0;
            //                 $dates = [];

            //                 foreach ($ovt_application_activities as $activity) {
            //                     $ovt_application_date_times = OvtApplicationDatetime::where('ovt_application_activity_id', $activity->id)->get();

            //                     foreach ($ovt_application_date_times as $ovt_application_date_time) {
            //                         $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
            //                         $timeTo = Carbon::parse($ovt_application_date_time->time_to);
            //                         $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
            //                         $total_in_minutes += $timeInMinutes;

            //                         $dates[] = $ovt_application_date_time->date;
            //                     }
            //                 }

            //                 $total_days = count(array_unique($dates));

            //                 $total_hours_credit = number_format($total_in_minutes / 60, 1);
            //                 $hours = floor($total_hours_credit);
            //                 $minutes = round(($total_hours_credit - $hours) * 60);

            //                 if ($minutes > 0) {
            //                     $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
            //                 } else {
            //                     $result = sprintf('%d hours', $hours, $total_days);
            //                 }
            //             } else {
            //                 $total_in_minutes = 0;
            //                 $dates = [];
            //                 $ovt_application_date_times = OvtApplicationDatetime::where('overtime_application_id', $overtime_application->id)->get();
            //                     foreach ($ovt_application_date_times as $ovt_application_date_time) {
            //                         $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
            //                         $timeTo = Carbon::parse($ovt_application_date_time->time_to);
            //                         $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
            //                         $total_in_minutes += $timeInMinutes;

            //                         $dates[] = $ovt_application_date_time->date;
            //                     }

            //                     $total_days = count(array_unique($dates));

            //                     $total_hours_credit = number_format($total_in_minutes / 60, 1);
            //                     $hours = floor($total_hours_credit);
            //                     $minutes = round(($total_hours_credit - $hours) * 60);

            //                     if ($minutes > 0) {
            //                         $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
            //                     } else {
            //                         $result = sprintf('%d hours', $hours, $total_days);
            //                     }

            //             }
            //             $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
            //             $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
            //             return [
            //                 'id' => $overtime_application->id,
            //                 'total_days'=>$total_days,
            //                 'total_hours'=>$result,
            //                 'reason' => $overtime_application->reason,
            //                 'remarks' => $overtime_application->remarks,
            //                 'purpose' => $overtime_application->purpose,
            //                 'status' => $overtime_application->status,
            //                 'overtime_letter' => $overtime_application->overtime_letter_of_request,
            //                 'employee_id' => $overtime_application->employee_profile_id,
            //                 'employee_name' => "{$first_name} {$last_name}",
            //                 'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
            //                 'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
            //                 'date_created' => $overtime_application->created_at,
            //                 'division_head' => $chief_name,
            //                 'division_head_position' => $chief_position,
            //                 'division_head_code' => $chief_code,
            //                 'department_head' => $head_name,
            //                 'department_head_position' => $head_position,
            //                 'department_head_code' => $head_code,
            //                 'section_head' => $supervisor_name,
            //                 'section_head_position' => $supervisor_position,
            //                 'section_head_code' => $supervisor_code,
            //                 'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
            //                 'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
            //                 'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
            //                 'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
            //                 'date' => $overtime_application->date,
            //                 'time' => $overtime_application->time,
            //                 'logs' => $logsData->map(function ($log) {
            //                     $process_name = $log->action;
            //                     $action = "";
            //                     $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
            //                     $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
            //                     if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
            //                         $action =  $process_name . ' by ' . 'Division Head';
            //                     } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
            //                         $action =  $process_name . ' by ' . 'Supervisor';
            //                     } else {
            //                         $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
            //                     }
            //                     $date = $log->date;
            //                     $formatted_date = Carbon::parse($date)->format('M d,Y');
            //                     return [
            //                         'id' => $log->id,
            //                         'overtime_application_id' => $log->overtime_application_id,
            //                         'action_by' => "{$first_name} {$last_name}",
            //                         'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
            //                         'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
            //                         'action' => $log->action,
            //                         'date' => $formatted_date,
            //                         'time' => $log->time,
            //                         'process' => $action
            //                     ];
            //                 }),
            //                 'activities' => $activitiesData->map(function ($activity) {
            //                     return [
            //                         'id' => $activity->id,
            //                         'overtime_application_id' => $activity->overtime_application_id,
            //                         'name' => $activity->name,
            //                         'quantity' => $activity->quantity,
            //                         'man_hour' => $activity->man_hour,
            //                         'period_covered' => $activity->period_covered,
            //                         'dates' => $activity->dates->map(function ($date) {
            //                             return [
            //                                 'id' => $date->id,
            //                                 'ovt_activity_id' => $date->ovt_application_activity_id,
            //                                 'time_from' => $date->time_from,
            //                                 'time_to' => $date->time_to,
            //                                 'date' => $date->date,
            //                                 'employees' => $date->employees->map(function ($employee) {
            //                                     $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
            //                                     $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
            //                                     return [
            //                                         'id' => $employee->id,
            //                                         'ovt_employee_id' => $employee->ovt_application_datetime_id,
            //                                         'employee_id' => $employee->employee_profile_id,
            //                                         'employee_name' => "{$first_name} {$last_name}",
            //                                         'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
            //                                     ];
            //                                 }),

            //                             ];
            //                         }),
            //                     ];
            //                 }),
            //                 'dates' => $datesData->map(function ($date) {
            //                     return [
            //                         'id' => $date->id,
            //                         'ovt_activity_id' => $date->ovt_application_activity_id,
            //                         'time_from' => $date->time_from,
            //                         'time_to' => $date->time_to,
            //                         'date' => $date->date,
            //                         'employees' => $date->employees->map(function ($employee) {
            //                             $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
            //                             $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
            //                             return [
            //                                 'id' => $employee->id,
            //                                 'ovt_employee_id' => $employee->ovt_application_datetime_id,
            //                                 'employee_id' => $employee->employee_profile_id,
            //                                 'employee_name' => "{$first_name} {$last_name}",
            //                                 'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
            //                             ];
            //                         }),
            //                     ];
            //                 }),

            //             ];
            //         });
            //         return response()->json(['data' => $overtime_applications_result]);
            //     } else {
            //         return response()->json(['message' => 'No records available'], Response::HTTP_OK);
            //     }
            // }
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getOvertimeApplications', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getDivisionOvertimeApplications(Request $request)
    {
        try {
            $id = '1';
            $status = $request->status;
            $division = AssignArea::where('employee_profile_id', $id)->value('division_id');
            $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
            if ($divisionHeadId == $id) {
                $OvertimeApplication = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'activities'])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                        $query->where('id', $division);
                    })
                    // ->where('status', 'for-approval-division-head')
                    ->get();
                if ($OvertimeApplication->isNotEmpty()) {
                    $overtime_applications_result = $OvertimeApplication->map(function ($overtime_application) {
                        $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                        $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                        $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                        $chief_name = null;
                        $chief_position = null;
                        $chief_code = null;
                        $head_name = null;
                        $head_position = null;
                        $head_code = null;
                        $supervisor_name = null;
                        $supervisor_position = null;
                        $supervisor_code = null;
                        if ($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                                $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                                $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($department) {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                $head_code = $department_name->head->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($section) {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                            }
                        }
                        $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $overtime_application->id,
                            'reason' => $overtime_application->reason,
                            'remarks' => $overtime_application->remarks,
                            'purpose' => $overtime_application->purpose,
                            'status' => $overtime_application->status,
                            'overtime_letter' => $overtime_application->overtime_letter_of_request,
                            'employee_id' => $overtime_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}",
                            'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $overtime_application->created_at,
                            'division_head' => $chief_name,
                            'division_head_position' => $chief_position,
                            'division_head_code' => $chief_code,
                            'department_head' => $head_name,
                            'department_head_position' => $head_position,
                            'department_head_code' => $head_code,
                            'section_head' => $supervisor_name,
                            'section_head_position' => $supervisor_position,
                            'section_head_code' => $supervisor_code,
                            'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                            'date' => $overtime_application->date,
                            'time' => $overtime_application->time,
                            'logs' => $logsData->map(function ($log) {
                                $process_name = $log->action;
                                $action = "";
                                $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                                $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Division Head';
                                } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Supervisor';
                                } else {
                                    $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                                }

                                $date = $log->date;
                                $formatted_date = Carbon::parse($date)->format('M d,Y');
                                return [
                                    'id' => $log->id,
                                    'overtime_application_id' => $log->overtime_application_id,
                                    'action_by' => "{$first_name} {$last_name}",
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'activities' => $activitiesData->map(function ($activity) {
                                return [
                                    'id' => $activity->id,
                                    'overtime_application_id' => $activity->overtime_application_id,
                                    'name' => $activity->name,
                                    'quantity' => $activity->quantity,
                                    'man_hour' => $activity->man_hour,
                                    'period_covered' => $activity->period_covered,
                                    'dates' => $activity->dates->map(function ($date) {
                                        return [
                                            'id' => $date->id,
                                            'ovt_activity_id' => $date->ovt_application_activity_id,
                                            'time_from' => $date->time_from,
                                            'time_to' => $date->time_to,
                                            'date' => $date->date,
                                            'employees' => $date->employees->map(function ($employee) {
                                                $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                                $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                                return [
                                                    'id' => $employee->id,
                                                    'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                                    'employee_id' => $employee->employee_profile_id,
                                                    'employee_name' => "{$first_name} {$last_name}",

                                                ];
                                            }),

                                        ];
                                    }),
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
                                return [
                                    'id' => $date->id,
                                    'ovt_activity_id' => $date->ovt_application_activity_id,
                                    'time_from' => $date->time_from,
                                    'time_to' => $date->time_to,
                                    'date' => $date->date,
                                    'employees' => $date->employees->map(function ($employee) {
                                        $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                        $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                        return [
                                            'id' => $employee->id,
                                            'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                            'employee_id' => $employee->employee_profile_id,
                                            'employee_name' => "{$first_name} {$last_name}",

                                        ];
                                    }),
                                ];
                            }),

                        ];
                    });
                    return response()->json(['data' => $overtime_applications_result]);
                } else {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }
            }
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getDivisionOvertimeApplications', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getDepartmentOvertimeApplications(Request $request)
    {
        try {
            $id = '1';
            $status = $request->status;
            $department = AssignArea::where('employee_profile_id', $id)->value('department_id');
            $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
            $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
            if ($departmentHeadId == $id || $training_officer_id == $id) {
                $OvertimeApplication = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'activities'])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                        $query->where('id', $department);
                    })
                    // ->where('status', 'for-approval-department-head')
                    ->get();
                if ($OvertimeApplication->isNotEmpty()) {
                    $overtime_applications_result = $OvertimeApplication->map(function ($overtime_application) {
                        $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                        $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                        $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                        $chief_name = null;
                        $chief_position = null;
                        $chief_code = null;
                        $head_name = null;
                        $head_position = null;
                        $head_code = null;
                        $supervisor_name = null;
                        $supervisor_position = null;
                        $supervisor_code = null;
                        if ($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                                $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                                $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($department) {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                $head_code = $department_name->head->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($section) {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                            }
                        }
                        $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $overtime_application->id,
                            'reason' => $overtime_application->reason,
                            'remarks' => $overtime_application->remarks,
                            'purpose' => $overtime_application->purpose,
                            'status' => $overtime_application->status,
                            'overtime_letter' => $overtime_application->overtime_letter_of_request,
                            'employee_id' => $overtime_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}",
                            'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $overtime_application->created_at,
                            'division_head' => $chief_name,
                            'division_head_position' => $chief_position,
                            'division_head_code' => $chief_code,
                            'department_head' => $head_name,
                            'department_head_position' => $head_position,
                            'department_head_code' => $head_code,
                            'section_head' => $supervisor_name,
                            'section_head_position' => $supervisor_position,
                            'section_head_code' => $supervisor_code,
                            'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                            'date' => $overtime_application->date,
                            'time' => $overtime_application->time,
                            'logs' => $logsData->map(function ($log) {
                                $process_name = $log->action;
                                $action = "";
                                $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                                $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Division Head';
                                } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Supervisor';
                                } else {
                                    $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                                }

                                $date = $log->date;
                                $formatted_date = Carbon::parse($date)->format('M d,Y');
                                return [
                                    'id' => $log->id,
                                    'overtime_application_id' => $log->overtime_application_id,
                                    'action_by' => "{$first_name} {$last_name}",
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'activities' => $activitiesData->map(function ($activity) {
                                return [
                                    'id' => $activity->id,
                                    'overtime_application_id' => $activity->overtime_application_id,
                                    'name' => $activity->name,
                                    'quantity' => $activity->quantity,
                                    'man_hour' => $activity->man_hour,
                                    'period_covered' => $activity->period_covered,
                                    'dates' => $activity->dates->map(function ($date) {
                                        return [
                                            'id' => $date->id,
                                            'ovt_activity_id' => $date->ovt_application_activity_id,
                                            'time_from' => $date->time_from,
                                            'time_to' => $date->time_to,
                                            'date' => $date->date,
                                            'employees' => $date->employees->map(function ($employee) {
                                                $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                                $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                                return [
                                                    'id' => $employee->id,
                                                    'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                                    'employee_id' => $employee->employee_profile_id,
                                                    'employee_name' => "{$first_name} {$last_name}",
                                                ];
                                            }),

                                        ];
                                    }),
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
                                return [
                                    'id' => $date->id,
                                    'ovt_activity_id' => $date->ovt_application_activity_id,
                                    'time_from' => $date->time_from,
                                    'time_to' => $date->time_to,
                                    'date' => $date->date,
                                    'employees' => $date->employees->map(function ($employee) {
                                        $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                        $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                        return [
                                            'id' => $employee->id,
                                            'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                            'employee_id' => $employee->employee_profile_id,
                                            'employee_name' => "{$first_name} {$last_name}",
                                        ];
                                    }),
                                ];
                            }),
                        ];
                    });
                    return response()->json(['data' => $overtime_applications_result]);
                } else {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }
            }
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getDepartmentOvertimeApplications', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getSectionOvertimeApplications(Request $request)
    {
        try {
            $id = '1';
            $status = $request->status;
            $section = AssignArea::where('employee_profile_id', $id)->value('section_id');
            $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
            if ($sectionHeadId == $id) {
                $overtime_applications = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'activities'])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                        $query->where('id', $section);
                    })
                    // ->where('status', 'for-approval-section-head')
                    ->get();
                if ($overtime_applications->isNotEmpty()) {
                    $overtime_applications_result = $overtime_applications->map(function ($overtime_application) {
                        $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                        $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                        $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                        $chief_name = null;
                        $chief_position = null;
                        $chief_code = null;
                        $head_name = null;
                        $head_position = null;
                        $head_code = null;
                        $supervisor_name = null;
                        $supervisor_position = null;
                        $supervisor_code = null;
                        if ($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                                $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                                $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($department) {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                $head_code = $department_name->head->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($section) {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                            }
                        }
                        $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $overtime_application->id,
                            'reason' => $overtime_application->reason,
                            'remarks' => $overtime_application->remarks,
                            'purpose' => $overtime_application->purpose,
                            'status' => $overtime_application->status,
                            'overtime_letter' => $overtime_application->overtime_letter_of_request,
                            'employee_id' => $overtime_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}",
                            'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $overtime_application->created_at,
                            'division_head' => $chief_name,
                            'division_head_position' => $chief_position,
                            'division_head_code' => $chief_code,
                            'department_head' => $head_name,
                            'department_head_position' => $head_position,
                            'department_head_code' => $head_code,
                            'section_head' => $supervisor_name,
                            'section_head_position' => $supervisor_position,
                            'section_head_code' => $supervisor_code,
                            'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                            'date' => $overtime_application->date,
                            'time' => $overtime_application->time,
                            'logs' => $logsData->map(function ($log) {
                                $process_name = $log->action;
                                $action = "";
                                $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                                $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Division Head';
                                } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Supervisor';
                                } else {
                                    $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                                }
                                $date = $log->date;
                                $formatted_date = Carbon::parse($date)->format('M d,Y');
                                return [
                                    'id' => $log->id,
                                    'overtime_application_id' => $log->overtime_application_id,
                                    'action_by' => "{$first_name} {$last_name}",
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'activities' => $activitiesData->map(function ($activity) {
                                return [
                                    'id' => $activity->id,
                                    'overtime_application_id' => $activity->overtime_application_id,
                                    'name' => $activity->name,
                                    'quantity' => $activity->quantity,
                                    'man_hour' => $activity->man_hour,
                                    'period_covered' => $activity->period_covered,
                                    'dates' => $activity->dates->map(function ($date) {
                                        return [
                                            'id' => $date->id,
                                            'ovt_activity_id' => $date->ovt_application_activity_id,
                                            'time_from' => $date->time_from,
                                            'time_to' => $date->time_to,
                                            'date' => $date->date,
                                            'employees' => $date->employees->map(function ($employee) {
                                                $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                                $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                                return [
                                                    'id' => $employee->id,
                                                    'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                                    'employee_id' => $employee->employee_profile_id,
                                                    'employee_name' => "{$first_name} {$last_name}",
                                                ];
                                            }),

                                        ];
                                    }),
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
                                return [
                                    'id' => $date->id,
                                    'ovt_activity_id' => $date->ovt_application_activity_id,
                                    'time_from' => $date->time_from,
                                    'time_to' => $date->time_to,
                                    'date' => $date->date,
                                    'employees' => $date->employees->map(function ($employee) {
                                        $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                        $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                        return [
                                            'id' => $employee->id,
                                            'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                            'employee_id' => $employee->employee_profile_id,
                                            'employee_name' => "{$first_name} {$last_name}",
                                        ];
                                    }),
                                ];
                            }),

                        ];
                    });
                    return response()->json(['data' => $overtime_applications_result]);
                } else {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }
            }
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getSectionOvertimeApplications', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getDeclinedOvertimeApplications(Request $request)
    {
        try {
            $id = '1';
            $overtime_applications = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'activities'])
                // ->where('status', 'declined')
                ->get();
            if ($overtime_applications->isNotEmpty()) {
                $overtime_applications_result = $overtime_applications->map(function ($overtime_application) {
                    $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                    $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                    $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                    $chief_name = null;
                    $chief_position = null;
                    $chief_code = null;
                    $head_name = null;
                    $head_position = null;
                    $head_code = null;
                    $supervisor_name = null;
                    $supervisor_position = null;
                    $supervisor_code = null;
                    if ($division) {
                        $division_name = Division::with('chief.personalInformation')->find($division);

                        if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                            $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                            $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                            $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                        }
                    }
                    if ($department) {
                        $department_name = Department::with('head.personalInformation')->find($department);
                        if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                            $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                            $head_position = $department_name->head->assignedArea->designation->name ?? null;
                            $head_code = $department_name->head->assignedArea->designation->code ?? null;
                        }
                    }
                    if ($section) {
                        $section_name = Section::with('supervisor.personalInformation')->find($section);
                        if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                            $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                            $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                            $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                        }
                    }
                    $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $overtime_application->id,
                        'reason' => $overtime_application->reason,
                        'remarks' => $overtime_application->remarks,
                        'purpose' => $overtime_application->purpose,
                        'status' => $overtime_application->status,
                        'overtime_letter' => $overtime_application->overtime_letter_of_request,
                        'employee_id' => $overtime_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}",
                        'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $overtime_application->created_at,
                        'division_head' => $chief_name,
                        'division_head_position' => $chief_position,
                        'division_head_code' => $chief_code,
                        'department_head' => $head_name,
                        'department_head_position' => $head_position,
                        'department_head_code' => $head_code,
                        'section_head' => $supervisor_name,
                        'section_head_position' => $supervisor_position,
                        'section_head_code' => $supervisor_code,
                        'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                        'date' => $overtime_application->date,
                        'time' => $overtime_application->time,
                        'logs' => $logsData->map(function ($log) {
                            $process_name = $log->action;
                            $action = "";
                            $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                            if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                                $action =  $process_name . ' by ' . 'Division Head';
                            } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                                $action =  $process_name . ' by ' . 'Supervisor';
                            } else {
                                $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                            }

                            $date = $log->date;
                            $formatted_date = Carbon::parse($date)->format('M d,Y');
                            return [
                                'id' => $log->id,
                                'overtime_application_id' => $log->overtime_application_id,
                                'action_by' => "{$first_name} {$last_name}",
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'activities' => $activitiesData->map(function ($activity) {
                            return [
                                'id' => $activity->id,
                                'overtime_application_id' => $activity->overtime_application_id,
                                'name' => $activity->name,
                                'quantity' => $activity->quantity,
                                'man_hour' => $activity->man_hour,
                                'period_covered' => $activity->period_covered,
                                'dates' => $activity->dates->map(function ($date) {
                                    return [
                                        'id' => $date->id,
                                        'ovt_activity_id' => $date->ovt_application_activity_id,
                                        'time_from' => $date->time_from,
                                        'time_to' => $date->time_to,
                                        'date' => $date->date,
                                        'employees' => $date->employees->map(function ($employee) {
                                            $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                            $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                            return [
                                                'id' => $employee->id,
                                                'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                                'employee_id' => $employee->employee_profile_id,
                                                'employee_name' => "{$first_name} {$last_name}",

                                            ];
                                        }),

                                    ];
                                }),
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
                            return [
                                'id' => $date->id,
                                'ovt_activity_id' => $date->ovt_application_activity_id,
                                'time_from' => $date->time_from,
                                'time_to' => $date->time_to,
                                'date' => $date->date,
                                'employees' => $date->employees->map(function ($employee) {
                                    $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                    $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                    return [
                                        'id' => $employee->id,
                                        'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                        'employee_id' => $employee->employee_profile_id,
                                        'employee_name' => "{$first_name} {$last_name}",

                                    ];
                                }),
                            ];
                        }),

                    ];
                });


                return response()->json(['official_business_applications' => $overtime_applications_result]);
            } else {
                return response()->json(['message' => 'No records available'], Response::HTTP_OK);
            }
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getDeclinedOvertimeApplications', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEmployeeOvertimeTotal()
    {
        try {
            $employeeProfiles = EmployeeProfile::with(['overtimeCredits', 'personalInformation'])
                ->get();
            $employeeOvertimeTotals = $employeeProfiles->map(function ($employeeProfile) {
                $totalAddCredits = $employeeProfile->overtimeCredits->where('operation', 'add')->sum('overtime_hours');
                $totalDeductCredits = $employeeProfile->overtimeCredits->where('operation', 'deduct')->sum('overtime_hours');
                $totalOvertimeCredits = $totalAddCredits - $totalDeductCredits;
                return [
                    'employee_id' => $employeeProfile->id,
                    'employee_name' => $employeeProfile->personalInformation->first_name,
                    'total_overtime_credits' => $totalOvertimeCredits,
                ];
            });

            return response()->json(['data' => $employeeOvertimeTotals], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getEmployeeOvertimeTotal', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEmployees()
    {
        try {
            $currentMonth = date('m');
            $currentYear = date('Y');
            $filteredEmployees = EmployeeProfile::with(['overtimeCredits', 'personalInformation']) // Eager load the 'overtimeCredits' and 'profileInformation' relationships
                ->get()
                ->filter(function ($employeeProfile) use ($currentMonth, $currentYear) {
                    $totalAddCredits = $employeeProfile->overtimeCredits->where('operation', 'add')->sum('credit_value');
                    $totalDeductCredits = $employeeProfile->overtimeCredits->where('operation', 'deduct')->sum('credit_value');

                    $totalOvertimeCredits = $totalAddCredits - $totalDeductCredits;

                    return $totalOvertimeCredits < 40 && $totalOvertimeCredits < 120;
                })
                ->map(function ($employeeProfile) {
                    $totalAddCredits = $employeeProfile->overtimeCredits->where('operation', 'add')->sum('overtime_hours');
                    $totalDeductCredits = $employeeProfile->overtimeCredits->where('operation', 'deduct')->sum('overtime_hours');

                    $totalOvertimeCredits = $totalAddCredits - $totalDeductCredits;

                    return [
                        'employee_id' => $employeeProfile->id,
                        'employee_name' => $employeeProfile->personalInformation->first_name, // Assuming 'name' is the field in the ProfileInformation model representing the employee name
                        'total_overtime_credits' => $totalOvertimeCredits,
                    ];
                });

            return response()->json(['data' => $filteredEmployees], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getEmployees', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function computeEmployees()
    {
        $currentMonth = date('m'); // Current month as two digits
        $currentYear = date('Y'); // Current year

        $employees = EmployeeProfile::with(['overtimeCredits', 'personalInformation'])->get();

        $results = [];

        foreach ($employees as $employee) {
            $overtimeCredits = $employee->overtimeCredits ?? collect(); // Handle null relationship
            $monthTotalAdd = $overtimeCredits
                ->filter(function ($item) use ($currentMonth) {
                    return Carbon::parse($item->date)->format('m') == $currentMonth && $item->operation == 'add';
                })
                ->sum('credit_value');

            $monthTotalDeduct = $overtimeCredits
                ->filter(function ($item) use ($currentMonth) {
                    return Carbon::parse($item->date)->format('m') == $currentMonth && $item->operation == 'deduct';
                })
                ->sum('credit_value');

            $yearTotalAdd = $overtimeCredits
                ->filter(function ($item) use ($currentYear) {
                    return Carbon::parse($item->date)->format('Y') == $currentYear && $item->operation == 'add';
                })
                ->sum('credit_value');

            $yearTotalDeduct = $overtimeCredits
                ->filter(function ($item) use ($currentYear) {
                    return Carbon::parse($item->date)->format('Y') == $currentYear && $item->operation == 'deduct';
                })
                ->sum('credit_value');

            // Calculate net totals
            $monthTotal = $monthTotalAdd - $monthTotalDeduct;
            $yearTotal = $yearTotalAdd - $yearTotalDeduct;

            // Grand Total
            $grandTotal = $employee->overtimeCredits->sum('credit_value');

            // Check if the credit limits are exceeded
            $creditExceededMonth = $monthTotal < 4;
            $creditExceededYear = $yearTotal < 120;

            $results[] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->personalInformation->first_name,
                // 'month_total_add' => $monthTotalAdd,
                // 'month_total_deduct' => $monthTotalDeduct,
                'month_credit_total' => $monthTotal,
                // 'year_total_add' => $yearTotalAdd,
                // 'year_total_deduct' => $yearTotalDeduct,
                'year_credit_total' => $yearTotal,
                // 'grand_total' => $grandTotal,

            ];
        }

        return $results;
    }

    public function store(Request $request)
    {
        try {
            // $user = $request->user;
            // $area = AssignArea::where('employee_profile_id', $user->id)->value('division_id');
            $validatedData = $request->validate([
                'dates.*' => 'required',
                'activities.*' => 'required',
                'time_from.*' => 'required',
                'time_to.*' => [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        $index = explode('.', $attribute)[1];
                        $timeFrom = $request->input('time_from.*' . $index);
                        if ($value < $timeFrom) {
                            $fail("The time to must be greater than time from.");
                        }
                    },
                ],
                //'letter_of_request' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'purpose.*' => 'required',
                'remarks.*' => 'required',
                'quantities.*' => 'required',
                'employees.*' => 'required',
            ]);
            $user = $request->user;
            $area = AssignArea::where('employee_profile_id', $user->id)->value('division_id');
            $divisions = Division::where('id', $area)->first();
            DB::beginTransaction();
            $path = "";
            $fileName = "";
            $file_name_encrypted = "";
            $size = "";
            if ($request->hasFile('letter_of_request')) {
                $fileName = pathinfo($request->file('letter_of_request')->getClientOriginalName(), PATHINFO_FILENAME);
                $size = filesize($request->file('letter_of_request'));
                $file_name_encrypted = Helpers::checkSaveFile($request->file('letter_of_request'), '/overtime_application');
            }
            $status = 'for-approval-division-head';
            $overtime_application = OvertimeApplication::create([
                'employee_profile_id' => $user->id,
                'status' => $status,
                'purpose' => $request->purpose,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'overtime_letter_of_request' =>  $fileName,
                'overtime_letter_of_request_path' =>  $file_name_encrypted,
                'overtime_letter_of_request_size' =>  $size,
                'path' =>  $path
            ]);

            $ovt_id = $overtime_application->id;
            foreach ($validatedData['activities'] as $index => $activities) {
                $activity_application = OvtApplicationActivity::create([
                    'overtime_application_id' => $ovt_id,
                    'name' => $activities,
                    'quantity' => $validatedData['quantities'][$index],
                ]);


                foreach ($validatedData['dates'][$index] as $dateIndex => $date) {
                    $date_application = OvtApplicationDatetime::create([
                        'ovt_application_activity_id' => $activity_application->id,
                        'time_from' => $validatedData['time_from'][$index][$dateIndex],
                        'time_to' => $validatedData['time_to'][$index][$dateIndex],
                        'date' => $date,
                    ]);


                    foreach ($validatedData['employees'][$index][$dateIndex] as $employee) {
                        OvtApplicationEmployee::create([
                            'ovt_application_datetime_id' => $date_application->id,
                            'employee_profile_id' => $employee,
                            // 'remarks' => $validatedData['remarks'][$index][$dateIndex][$employeeIndex],
                        ]);
                    }
                }
            }


            $columnsString = "";
            $process_name = "Applied";
            $user = $request->user;
            $this->storeOvertimeApplicationLog($ovt_id, $process_name, $columnsString,$user->id);
            DB::commit();
            $overtime_applications = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'directDates'])
                ->where('id', $ovt_id)->get();
            $overtime_applications_result = $overtime_applications->map(function ($overtime_application) {
                $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                $chief_name = null;
                $chief_position = null;
                $chief_code = null;
                $head_name = null;
                $head_position = null;
                $head_code = null;
                $supervisor_name = null;
                $supervisor_position = null;
                $supervisor_code = null;
                if ($division) {
                    $division_name = Division::with('chief.personalInformation')->find($division);

                    if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                        $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                        $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                        $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                    }
                }
                if ($department) {
                    $department_name = Department::with('head.personalInformation')->find($department);
                    if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                        $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                        $head_position = $department_name->head->assignedArea->designation->name ?? null;
                        $head_code = $department_name->head->assignedArea->designation->code ?? null;
                    }
                }
                if ($section) {
                    $section_name = Section::with('supervisor.personalInformation')->find($section);
                    if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                        $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                        $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                        $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                    }
                }
                $ovt_application_activities = OvtApplicationActivity::where('overtime_application_id', $overtime_application->id)->get();
                if ($ovt_application_activities->isNotEmpty()) {
                    $total_in_minutes = 0;
                    $dates = [];

                    foreach ($ovt_application_activities as $activity) {
                        $ovt_application_date_times = OvtApplicationDatetime::where('ovt_application_activity_id', $activity->id)->get();

                        foreach ($ovt_application_date_times as $ovt_application_date_time) {
                            $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                            $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                            $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                            $total_in_minutes += $timeInMinutes;

                            $dates[] = $ovt_application_date_time->date;
                        }
                    }

                    $total_days = count(array_unique($dates));

                    $total_hours_credit = number_format($total_in_minutes / 60, 1);
                    $hours = floor($total_hours_credit);
                    $minutes = round(($total_hours_credit - $hours) * 60);

                    if ($minutes > 0) {
                        $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                    } else {
                        $result = sprintf('%d hours', $hours, $total_days);
                    }
                } else {
                    $total_in_minutes = 0;
                    $dates = [];
                    $ovt_application_date_times = OvtApplicationDatetime::where('overtime_application_id', $overtime_application->id)->get();
                    foreach ($ovt_application_date_times as $ovt_application_date_time) {
                        $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                        $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                        $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                        $total_in_minutes += $timeInMinutes;

                        $dates[] = $ovt_application_date_time->date;
                    }

                    $total_days = count(array_unique($dates));

                    $total_hours_credit = number_format($total_in_minutes / 60, 1);
                    $hours = floor($total_hours_credit);
                    $minutes = round(($total_hours_credit - $hours) * 60);

                    if ($minutes > 0) {
                        $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                    } else {
                        $result = sprintf('%d hours', $hours, $total_days);
                    }
                }
                $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                return [
                    'id' => $overtime_application->id,
                    'total_days' => $total_days,
                    'total_hours' => $result,
                    'reason' => $overtime_application->reason,
                    'remarks' => $overtime_application->remarks,
                    'purpose' => $overtime_application->purpose,
                    'status' => $overtime_application->status,
                    'overtime_letter' => $overtime_application->overtime_letter_of_request,
                    'employee_id' => $overtime_application->employee_profile_id,
                    'employee_name' => "{$first_name} {$last_name}",
                    'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                    'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                    'date_created' => $overtime_application->created_at,
                    'division_head' => $chief_name,
                    'division_head_position' => $chief_position,
                    'division_head_code' => $chief_code,
                    'department_head' => $head_name,
                    'department_head_position' => $head_position,
                    'department_head_code' => $head_code,
                    'section_head' => $supervisor_name,
                    'section_head_position' => $supervisor_position,
                    'section_head_code' => $supervisor_code,
                    'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                    'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                    'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                    'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                    'date' => $overtime_application->date,
                    'time' => $overtime_application->time,
                    'logs' => $logsData->map(function ($log) {
                        $process_name = $log->action;
                        $action = "";
                        $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                        if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                            $action =  $process_name . ' by ' . 'Division Head';
                        } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                            $action =  $process_name . ' by ' . 'Supervisor';
                        } else {
                            $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                        }

                        $date = $log->date;
                        $formatted_date = Carbon::parse($date)->format('M d,Y');
                        return [
                            'id' => $log->id,
                            'overtime_application_id' => $log->overtime_application_id,
                            'action_by' => "{$first_name} {$last_name}",
                            'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                            'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                            'action' => $log->action,
                            'date' => $formatted_date,
                            'time' => $log->time,
                            'process' => $action
                        ];
                    }),
                    'activities' => $activitiesData->map(function ($activity) {
                        return [
                            'id' => $activity->id,
                            'overtime_application_id' => $activity->overtime_application_id,
                            'name' => $activity->name,
                            'quantity' => $activity->quantity,
                            'man_hour' => $activity->man_hour,
                            'period_covered' => $activity->period_covered,
                            'dates' => $activity->dates->map(function ($date) {
                                return [
                                    'id' => $date->id,
                                    'ovt_activity_id' => $date->ovt_application_activity_id,
                                    'time_from' => $date->time_from,
                                    'time_to' => $date->time_to,
                                    'date' => $date->date,
                                    'employees' => $date->employees->map(function ($employee) {
                                        $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                        $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                        return [
                                            'id' => $employee->id,
                                            'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                            'employee_id' => $employee->employee_profile_id,
                                            'employee_name' => "{$first_name} {$last_name}",
                                            'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                        ];
                                    }),

                                ];
                            }),
                        ];
                    }),
                    'dates' => $datesData->map(function ($date) {
                        return [
                            'id' => $date->id,
                            'ovt_activity_id' => $date->ovt_application_activity_id,
                            'time_from' => $date->time_from,
                            'time_to' => $date->time_to,
                            'date' => $date->date,
                            'employees' => $date->employees->map(function ($employee) {
                                $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                return [
                                    'id' => $employee->id,
                                    'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                    'employee_id' => $employee->employee_profile_id,
                                    'employee_name' => "{$first_name} {$last_name}",
                                    'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                ];
                            }),
                        ];
                    }),
                ];
            });

            return response()->json([
                'message' => 'Overtime Application has been sucessfully saved',
                'data' => $overtime_applications_result
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storePast(Request $request)
    {
        try {
            //  return $request->dates;
            $user = $request->user;
            $area = AssignArea::where('employee_profile_id', $user->id)->value('division_id');

            $validatedData = $request->validate([
                'dates.*' => 'required|date_format:Y-m-d',
                'time_from.*' => 'required|date_format:H:i',
                'time_to.*' => [
                    'required',
                    'date_format:H:i',
                    function ($attribute, $value, $fail) use ($request) {
                        $index = explode('.', $attribute)[1];
                        $timeFrom = $request->input('time_from.' . $index);
                        if ($value < $timeFrom) {
                            $fail("The time to must be greater than time from.");
                        }
                    },
                ],
                'remarks.*' => 'required|string|max:512',
                'employees' => 'required|array',
                'employees.*' => 'required|integer|exists:employee_profiles,id',
            ]);
            DB::beginTransaction();
            $path = "";
            // $divisions = Division::where('id',$area)->first();
            // if ($divisions->code === 'NS' || $divisions->code === 'MS') {

            //     $status='for-approval-department-head';
            // }
            // else
            // {
            //     $status='for-approval-section-head';
            // }
            $status = 'for-approval-division-head';
            $overtime_application = OvertimeApplication::create([
                'employee_profile_id' => $user->id,
                'reference_number' => '123',
                'status' => $status,
                'purpose' => $request->purpose,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
            ]);
            $ovt_id = $overtime_application->id;

            foreach ($validatedData['dates'] as $index => $date) {
                $date_application = OvtApplicationDatetime::create([
                    'overtime_application_id' => $ovt_id,
                    'time_from' =>  $validatedData['time_from'][$index],
                    'time_to' =>  $validatedData['time_to'][$index],
                    'date' =>  $date,
                ]);
            }
            $date_id = $date_application->id;
            foreach ($validatedData['employees'] as $index => $employees) {
                OvtApplicationEmployee::create([
                    'ovt_application_datetime_id' => $date_id,
                    'employee_profile_id' =>  $validatedData['employees'][$index],
                    'remarks' =>  $validatedData['remarks'][$index],
                ]);
            }
            $columnsString = "";
            $process_name = "Applied";
            $this->storeOvertimeApplicationLog($ovt_id, $process_name, $columnsString, $user->id);
            DB::commit();

            $overtime_applications = OvertimeApplication::with(['employeeProfile.assignedArea', 'employeeProfile.personalInformation', 'logs', 'directDates'])
                ->where('id', $ovt_id)->get();
            $overtime_applications_result = $overtime_applications->map(function ($overtime_application) {
                $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                $chief_name = null;
                $chief_position = null;
                $chief_code = null;
                $head_name = null;
                $head_position = null;
                $head_code = null;
                $supervisor_name = null;
                $supervisor_position = null;
                $supervisor_code = null;
                if ($division) {
                    $division_name = Division::with('chief.personalInformation')->find($division);

                    if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                        $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                        $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                        $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                    }
                }
                if ($department) {
                    $department_name = Department::with('head.personalInformation')->find($department);
                    if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                        $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                        $head_position = $department_name->head->assignedArea->designation->name ?? null;
                        $head_code = $department_name->head->assignedArea->designation->code ?? null;
                    }
                }
                if ($section) {
                    $section_name = Section::with('supervisor.personalInformation')->find($section);
                    if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                        $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                        $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                        $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                    }
                }
                $ovt_application_activities = OvtApplicationActivity::where('overtime_application_id', $overtime_application->id)->get();
                if ($ovt_application_activities->isNotEmpty()) {
                    $total_in_minutes = 0;
                    $dates = [];

                    foreach ($ovt_application_activities as $activity) {
                        $ovt_application_date_times = OvtApplicationDatetime::where('ovt_application_activity_id', $activity->id)->get();

                        foreach ($ovt_application_date_times as $ovt_application_date_time) {
                            $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                            $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                            $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                            $total_in_minutes += $timeInMinutes;

                            $dates[] = $ovt_application_date_time->date;
                        }
                    }

                    $total_days = count(array_unique($dates));

                    $total_hours_credit = number_format($total_in_minutes / 60, 1);
                    $hours = floor($total_hours_credit);
                    $minutes = round(($total_hours_credit - $hours) * 60);

                    if ($minutes > 0) {
                        $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                    } else {
                        $result = sprintf('%d hours', $hours, $total_days);
                    }
                } else {
                    $total_in_minutes = 0;
                    $dates = [];
                    $ovt_application_date_times = OvtApplicationDatetime::where('overtime_application_id', $overtime_application->id)->get();
                    foreach ($ovt_application_date_times as $ovt_application_date_time) {
                        $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                        $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                        $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                        $total_in_minutes += $timeInMinutes;

                        $dates[] = $ovt_application_date_time->date;
                    }

                    $total_days = count(array_unique($dates));

                    $total_hours_credit = number_format($total_in_minutes / 60, 1);
                    $hours = floor($total_hours_credit);
                    $minutes = round(($total_hours_credit - $hours) * 60);

                    if ($minutes > 0) {
                        $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                    } else {
                        $result = sprintf('%d hours', $hours, $total_days);
                    }
                }
                $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                return [
                    'id' => $overtime_application->id,
                    'total_days' => $total_days,
                    'total_hours' => $result,
                    'reason' => $overtime_application->reason,
                    'remarks' => $overtime_application->remarks,
                    'purpose' => $overtime_application->purpose,
                    'status' => $overtime_application->status,
                    'overtime_letter' => $overtime_application->overtime_letter_of_request,
                    'employee_id' => $overtime_application->employee_profile_id,
                    'employee_name' => "{$first_name} {$last_name}",
                    'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                    'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                    'date_created' => $overtime_application->created_at,
                    'division_head' => $chief_name,
                    'division_head_position' => $chief_position,
                    'division_head_code' => $chief_code,
                    'department_head' => $head_name,
                    'department_head_position' => $head_position,
                    'department_head_code' => $head_code,
                    'section_head' => $supervisor_name,
                    'section_head_position' => $supervisor_position,
                    'section_head_code' => $supervisor_code,
                    'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                    'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                    'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                    'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                    'date' => $overtime_application->date,
                    'time' => $overtime_application->time,
                    'logs' => $logsData->map(function ($log) {
                        $process_name = $log->action;
                        $action = "";
                        $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                        if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                            $action =  $process_name . ' by ' . 'Division Head';
                        } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                            $action =  $process_name . ' by ' . 'Supervisor';
                        } else {
                            $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                        }

                        $date = $log->date;
                        $formatted_date = Carbon::parse($date)->format('M d,Y');
                        return [
                            'id' => $log->id,
                            'overtime_application_id' => $log->overtime_application_id,
                            'action_by' => "{$first_name} {$last_name}",
                            'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                            'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                            'action' => $log->action,
                            'date' => $formatted_date,
                            'time' => $log->time,
                            'process' => $action
                        ];
                    }),
                    'activities' => $activitiesData->map(function ($activity) {
                        return [
                            'id' => $activity->id,
                            'overtime_application_id' => $activity->overtime_application_id,
                            'name' => $activity->name,
                            'quantity' => $activity->quantity,
                            'man_hour' => $activity->man_hour,
                            'period_covered' => $activity->period_covered,
                            'dates' => $activity->dates->map(function ($date) {
                                return [
                                    'id' => $date->id,
                                    'ovt_activity_id' => $date->ovt_application_activity_id,
                                    'time_from' => $date->time_from,
                                    'time_to' => $date->time_to,
                                    'date' => $date->date,
                                    'employees' => $date->employees->map(function ($employee) {
                                        $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                        $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                        return [
                                            'id' => $employee->id,
                                            'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                            'employee_id' => $employee->employee_profile_id,
                                            'employee_name' => "{$first_name} {$last_name}",
                                            'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                        ];
                                    }),

                                ];
                            }),
                        ];
                    }),
                    'dates' => $datesData->map(function ($date) {
                        return [
                            'id' => $date->id,
                            'ovt_activity_id' => $date->ovt_application_activity_id,
                            'time_from' => $date->time_from,
                            'time_to' => $date->time_to,
                            'date' => $date->date,
                            'employees' => $date->employees->map(function ($employee) {
                                $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                return [
                                    'id' => $employee->id,
                                    'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                    'employee_id' => $employee->employee_profile_id,
                                    'employee_name' => "{$first_name} {$last_name}",
                                    'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                ];
                            }),
                        ];
                    }),

                ];
            });

            return response()->json(['message' => 'Overtime Application has been sucessfully saved', 'data' => $overtime_applications_result], Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'storePast', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storeOvertimeApplicationLog($overtime_application_id, $process_name, $changedfields, $user_id)
    {
        try {


            $data = [
                'overtime_application_id' => $overtime_application_id,
                'action_by_id' => $user_id,
                'action' => $process_name,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'fields' => $changedfields
            ];

            $overtime_application_log = OvtApplicationLog::create($data);

            return $overtime_application_log;
        } catch (\Exception $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'storeOvertimeApplicationLog', $th->getMessage());
            return response()->json(['message' => $th->getMessage(), 'error' => true], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function declineOtApplication($id, Request $request)
    {
        try {

            $overtime_applications = OvertimeApplication::where('id', '=', $id)
                ->first();
            if ($overtime_applications) {
                $user = $request->user;
                $user_password = $user->password_encrypted;
                $password = $request->password;
                if ($user_password == $password) {
                    // if($user_id){
                    DB::beginTransaction();
                    $overtime_application_log = new OvtApplicationLog();
                    $overtime_application_log->action = 'declined';
                    $overtime_application_log->overtime_application_id = $id;
                    $overtime_application_log->date = date('Y-m-d');
                    $overtime_application_log->time = date('h-i-s');
                    $overtime_application_log->action_by_id = $user->id;
                    $overtime_application_log->save();

                    $overtime_application = overtimeApplication::findOrFail($id);
                    $overtime_application->status = 'declined';
                    $overtime_application->decline_reason = $request->decline_reason;
                    $overtime_application->update();
                    DB::commit();

                    $overtime_applications = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'directDates'])
                        ->where('id', $overtime_application->id)->get();
                    $overtime_applications_result = $overtime_applications->map(function ($overtime_application) {
                        $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                        $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                        $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                        $chief_name = null;
                        $chief_position = null;
                        $chief_code = null;
                        $head_name = null;
                        $head_position = null;
                        $head_code = null;
                        $supervisor_name = null;
                        $supervisor_position = null;
                        $supervisor_code = null;
                        if ($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                                $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                                $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($department) {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                $head_code = $department_name->head->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($section) {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                            }
                        }
                        $ovt_application_activities = OvtApplicationActivity::where('overtime_application_id', $overtime_application->id)->get();
                        if ($ovt_application_activities->isNotEmpty()) {
                            $total_in_minutes = 0;
                            $dates = [];

                            foreach ($ovt_application_activities as $activity) {
                                $ovt_application_date_times = OvtApplicationDatetime::where('ovt_application_activity_id', $activity->id)->get();

                                foreach ($ovt_application_date_times as $ovt_application_date_time) {
                                    $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                                    $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                                    $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                                    $total_in_minutes += $timeInMinutes;

                                    $dates[] = $ovt_application_date_time->date;
                                }
                            }

                            $total_days = count(array_unique($dates));

                            $total_hours_credit = number_format($total_in_minutes / 60, 1);
                            $hours = floor($total_hours_credit);
                            $minutes = round(($total_hours_credit - $hours) * 60);

                            if ($minutes > 0) {
                                $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                            } else {
                                $result = sprintf('%d hours', $hours, $total_days);
                            }
                        } else {
                            $total_in_minutes = 0;
                            $dates = [];
                            $ovt_application_date_times = OvtApplicationDatetime::where('overtime_application_id', $overtime_application->id)->get();
                            foreach ($ovt_application_date_times as $ovt_application_date_time) {
                                $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                                $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                                $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                                $total_in_minutes += $timeInMinutes;

                                $dates[] = $ovt_application_date_time->date;
                            }

                            $total_days = count(array_unique($dates));

                            $total_hours_credit = number_format($total_in_minutes / 60, 1);
                            $hours = floor($total_hours_credit);
                            $minutes = round(($total_hours_credit - $hours) * 60);

                            if ($minutes > 0) {
                                $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                            } else {
                                $result = sprintf('%d hours', $hours, $total_days);
                            }
                        }
                        $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $overtime_application->id,
                            'total_days' => $total_days,
                            'total_hours' => $result,
                            'reason' => $overtime_application->reason,
                            'remarks' => $overtime_application->remarks,
                            'purpose' => $overtime_application->purpose,
                            'status' => $overtime_application->status,
                            'overtime_letter' => $overtime_application->overtime_letter_of_request,
                            'employee_id' => $overtime_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}",
                            'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $overtime_application->created_at,
                            'division_head' => $chief_name,
                            'division_head_position' => $chief_position,
                            'division_head_code' => $chief_code,
                            'department_head' => $head_name,
                            'department_head_position' => $head_position,
                            'department_head_code' => $head_code,
                            'section_head' => $supervisor_name,
                            'section_head_position' => $supervisor_position,
                            'section_head_code' => $supervisor_code,
                            'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                            'date' => $overtime_application->date,
                            'time' => $overtime_application->time,
                            'logs' => $logsData->map(function ($log) {
                                $process_name = $log->action;
                                $action = "";
                                $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                                $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Division Head';
                                } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Supervisor';
                                } else {
                                    $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                                }

                                $date = $log->date;
                                $formatted_date = Carbon::parse($date)->format('M d,Y');
                                return [
                                    'id' => $log->id,
                                    'overtime_application_id' => $log->overtime_application_id,
                                    'action_by' => "{$first_name} {$last_name}",
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'activities' => $activitiesData->map(function ($activity) {
                                return [
                                    'id' => $activity->id,
                                    'overtime_application_id' => $activity->overtime_application_id,
                                    'name' => $activity->name,
                                    'quantity' => $activity->quantity,
                                    'man_hour' => $activity->man_hour,
                                    'period_covered' => $activity->period_covered,
                                    'dates' => $activity->dates->map(function ($date) {
                                        return [
                                            'id' => $date->id,
                                            'ovt_activity_id' => $date->ovt_application_activity_id,
                                            'time_from' => $date->time_from,
                                            'time_to' => $date->time_to,
                                            'date' => $date->date,
                                            'employees' => $date->employees->map(function ($employee) {
                                                $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                                $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                                return [
                                                    'id' => $employee->id,
                                                    'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                                    'employee_id' => $employee->employee_profile_id,
                                                    'employee_name' => "{$first_name} {$last_name}",
                                                    'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                                ];
                                            }),

                                        ];
                                    }),
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
                                return [
                                    'id' => $date->id,
                                    'ovt_activity_id' => $date->ovt_application_activity_id,
                                    'time_from' => $date->time_from,
                                    'time_to' => $date->time_to,
                                    'date' => $date->date,
                                    'employees' => $date->employees->map(function ($employee) {
                                        $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                        $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                        return [
                                            'id' => $employee->id,
                                            'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                            'employee_id' => $employee->employee_profile_id,
                                            'employee_name' => "{$first_name} {$last_name}",
                                            'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                        ];
                                    }),
                                ];
                            }),
                        ];
                    });
                    return response(['message' => 'Application has been sucessfully declined', 'data' => $overtime_applications_result], Response::HTTP_OK);
                }
            }
        } catch (\Exception $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'declineOtApplication', $th->getMessage());
            return response()->json(['message' => $th->getMessage(), 'error' => true], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancelOtApplication($id, Request $request)
    {
        try {

            $overtime_applications = overtimeApplication::where('id', '=', $id)
                ->first();
            if ($overtime_applications) {
                // $user_id = Auth::user()->id;
                // $user = EmployeeProfile::where('id','=',$user_id)->first();
                // $user_password=$user->password;
                // $password=$request->password;
                // if($user_password==$password)
                // {
                //     if($user_id){
                DB::beginTransaction();
                $overtime_application_log = new OvtApplicationLog();
                $overtime_application_log->action = 'cancel';
                $overtime_application_log->overtime_application_id = $id;
                $overtime_application_log->date = date('Y-m-d');
                $overtime_application_log->action_by_id = '1';
                $overtime_application_log->save();

                $overtime_application = OvertimeApplication::findOrFail($id);
                $overtime_application->status = 'cancelled';
                $overtime_application->update();
                DB::commit();

                $overtime_applications = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'directDates'])
                    ->where('id', $overtime_application->id)->get();
                $overtime_applications_result = $overtime_applications->map(function ($overtime_application) {
                    $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                    $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                    $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                    $chief_name = null;
                    $chief_position = null;
                    $chief_code = null;
                    $head_name = null;
                    $head_position = null;
                    $head_code = null;
                    $supervisor_name = null;
                    $supervisor_position = null;
                    $supervisor_code = null;
                    if ($division) {
                        $division_name = Division::with('chief.personalInformation')->find($division);

                        if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                            $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                            $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                            $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                        }
                    }
                    if ($department) {
                        $department_name = Department::with('head.personalInformation')->find($department);
                        if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                            $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                            $head_position = $department_name->head->assignedArea->designation->name ?? null;
                            $head_code = $department_name->head->assignedArea->designation->code ?? null;
                        }
                    }
                    if ($section) {
                        $section_name = Section::with('supervisor.personalInformation')->find($section);
                        if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                            $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                            $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                            $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                        }
                    }
                    $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $overtime_application->id,
                        'reason' => $overtime_application->reason,
                        'remarks' => $overtime_application->remarks,
                        'purpose' => $overtime_application->purpose,
                        'status' => $overtime_application->status,
                        'overtime_letter' => $overtime_application->overtime_letter_of_request,
                        'employee_id' => $overtime_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}",
                        'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $overtime_application->created_at,
                        'division_head' => $chief_name,
                        'division_head_position' => $chief_position,
                        'division_head_code' => $chief_code,
                        'department_head' => $head_name,
                        'department_head_position' => $head_position,
                        'department_head_code' => $head_code,
                        'section_head' => $supervisor_name,
                        'section_head_position' => $supervisor_position,
                        'section_head_code' => $supervisor_code,
                        'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                        'date' => $overtime_application->date,
                        'time' => $overtime_application->time,
                        'logs' => $logsData->map(function ($log) {
                            $process_name = $log->action;
                            $action = "";
                            $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                            if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                                $action =  $process_name . ' by ' . 'Division Head';
                            } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                                $action =  $process_name . ' by ' . 'Supervisor';
                            } else {
                                $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                            }

                            $date = $log->date;
                            $formatted_date = Carbon::parse($date)->format('M d,Y');
                            return [
                                'id' => $log->id,
                                'overtime_application_id' => $log->overtime_application_id,
                                'action_by' => "{$first_name} {$last_name}",
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'activities' => $activitiesData->map(function ($activity) {
                            return [
                                'id' => $activity->id,
                                'overtime_application_id' => $activity->overtime_application_id,
                                'name' => $activity->name,
                                'quantity' => $activity->quantity,
                                'man_hour' => $activity->man_hour,
                                'period_covered' => $activity->period_covered,
                                'dates' => $activity->dates->map(function ($date) {
                                    return [
                                        'id' => $date->id,
                                        'ovt_activity_id' => $date->ovt_application_activity_id,
                                        'time_from' => $date->time_from,
                                        'time_to' => $date->time_to,
                                        'date' => $date->date,
                                        'employees' => $date->employees->map(function ($employee) {
                                            $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                            $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                            return [
                                                'id' => $employee->id,
                                                'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                                'employee_id' => $employee->employee_profile_id,
                                                'employee_name' => "{$first_name} {$last_name}",
                                            ];
                                        }),

                                    ];
                                }),
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
                            return [
                                'id' => $date->id,
                                'ovt_activity_id' => $date->ovt_application_activity_id,
                                'time_from' => $date->time_from,
                                'time_to' => $date->time_to,
                                'date' => $date->date,
                                'employees' => $date->employees->map(function ($employee) {
                                    $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                    $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                    return [
                                        'id' => $employee->id,
                                        'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                        'employee_id' => $employee->employee_profile_id,
                                        'employee_name' => "{$first_name} {$last_name}",

                                    ];
                                }),
                            ];
                        }),
                    ];
                });
                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $overtime_applications_result], Response::HTTP_OK);

                //     }
                //  }
            }
        } catch (\Exception $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'cancelOtApplication', $th->getMessage());
            return response()->json(['message' => $th->getMessage(), 'error' => true], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateOvertimeApplicationStatus($id, $status, Request $request)
    {
        try {
            $user = $request->user;
            $password_decrypted = Crypt::decryptString($user['password_encrypted']);
            $password = strip_tags($request->password);
            if (!Hash::check($password . Cache::get('salt_value'), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_FORBIDDEN);
            } else {
                $message_action = '';
                $action = '';
                $new_status = '';
                if ($status == 'for-approval-division-head') {
                    $action = 'Aprroved by Division Head';
                    $new_status = 'approved';
                    $message_action = "Approved";
                }
                if ($status == 'for-approval-omcc-head') {
                    $action = 'Aprroved by Omcc Head';
                    $new_status = 'approved';
                    $message_action = "Approved";
                }
                $overtime_applications = OvertimeApplication::where('id', '=', $id)
                    ->first();
                if ($overtime_applications) {
                    DB::beginTransaction();
                    $overtime_application_log = new OvtApplicationLog();
                    $overtime_application_log->action = $action;
                    $overtime_application_log->overtime_application_id = $id;
                    $overtime_application_log->action_by_id = '1';
                    $overtime_application_log->date = date('Y-m-d');
                    $overtime_application_log->time =  date('H:i:s');
                    $overtime_application_log->save();

                    $overtime_application = OvertimeApplication::findOrFail($id);
                    $overtime_application->status = $new_status;
                    $overtime_application->update();
                    DB::commit();

                    $overtime_applications = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'directDates'])
                        ->where('id', $overtime_application->id)->get();
                    $overtime_applications_result = $overtime_applications->map(function ($overtime_application) {
                        $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                        $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                        $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                        $chief_name = null;
                        $chief_position = null;
                        $chief_code = null;
                        $head_name = null;
                        $head_position = null;
                        $head_code = null;
                        $supervisor_name = null;
                        $supervisor_position = null;
                        $supervisor_code = null;
                        if ($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                                $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                                $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($department) {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                $head_code = $department_name->head->assignedArea->designation->code ?? null;
                            }
                        }
                        if ($section) {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                            }
                        }
                        $ovt_application_activities = OvtApplicationActivity::where('overtime_application_id', $overtime_application->id)->get();
                        if ($ovt_application_activities->isNotEmpty()) {
                            $total_in_minutes = 0;
                            $dates = [];

                            foreach ($ovt_application_activities as $activity) {
                                $ovt_application_date_times = OvtApplicationDatetime::where('ovt_application_activity_id', $activity->id)->get();

                                foreach ($ovt_application_date_times as $ovt_application_date_time) {
                                    $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                                    $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                                    $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                                    $total_in_minutes += $timeInMinutes;

                                    $dates[] = $ovt_application_date_time->date;
                                }
                            }

                            $total_days = count(array_unique($dates));

                            $total_hours_credit = number_format($total_in_minutes / 60, 1);
                            $hours = floor($total_hours_credit);
                            $minutes = round(($total_hours_credit - $hours) * 60);

                            if ($minutes > 0) {
                                $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                            } else {
                                $result = sprintf('%d hours', $hours, $total_days);
                            }
                        } else {
                            $total_in_minutes = 0;
                            $dates = [];
                            $ovt_application_date_times = OvtApplicationDatetime::where('overtime_application_id', $overtime_application->id)->get();
                            foreach ($ovt_application_date_times as $ovt_application_date_time) {
                                $timeFrom = Carbon::parse($ovt_application_date_time->time_from);
                                $timeTo = Carbon::parse($ovt_application_date_time->time_to);
                                $timeInMinutes = $timeFrom->diffInMinutes($timeTo);
                                $total_in_minutes += $timeInMinutes;

                                $dates[] = $ovt_application_date_time->date;
                            }

                            $total_days = count(array_unique($dates));

                            $total_hours_credit = number_format($total_in_minutes / 60, 1);
                            $hours = floor($total_hours_credit);
                            $minutes = round(($total_hours_credit - $hours) * 60);

                            if ($minutes > 0) {
                                $result = sprintf('%d hours and %d minutes', $hours, $minutes, $total_days);
                            } else {
                                $result = sprintf('%d hours', $hours, $total_days);
                            }
                        }
                        $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $overtime_application->id,
                            'total_days' => $total_days,
                            'total_hours' => $result,
                            'reason' => $overtime_application->reason,
                            'remarks' => $overtime_application->remarks,
                            'purpose' => $overtime_application->purpose,
                            'status' => $overtime_application->status,
                            'overtime_letter' => $overtime_application->overtime_letter_of_request,
                            'employee_id' => $overtime_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}",
                            'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $overtime_application->created_at,
                            'division_head' => $chief_name,
                            'division_head_position' => $chief_position,
                            'division_head_code' => $chief_code,
                            'department_head' => $head_name,
                            'department_head_position' => $head_position,
                            'department_head_code' => $head_code,
                            'section_head' => $supervisor_name,
                            'section_head_position' => $supervisor_position,
                            'section_head_code' => $supervisor_code,
                            'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                            'date' => $overtime_application->date,
                            'time' => $overtime_application->time,
                            'logs' => $logsData->map(function ($log) {
                                $process_name = $log->action;
                                $action = "";
                                $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                                $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Division Head';
                                } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                                    $action =  $process_name . ' by ' . 'Supervisor';
                                } else {
                                    $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                                }

                                $date = $log->date;
                                $formatted_date = Carbon::parse($date)->format('M d,Y');
                                return [
                                    'id' => $log->id,
                                    'overtime_application_id' => $log->overtime_application_id,
                                    'action_by' => "{$first_name} {$last_name}",
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'activities' => $activitiesData->map(function ($activity) {
                                return [
                                    'id' => $activity->id,
                                    'overtime_application_id' => $activity->overtime_application_id,
                                    'name' => $activity->name,
                                    'quantity' => $activity->quantity,
                                    'man_hour' => $activity->man_hour,
                                    'period_covered' => $activity->period_covered,
                                    'dates' => $activity->dates->map(function ($date) {
                                        return [
                                            'id' => $date->id,
                                            'ovt_activity_id' => $date->ovt_application_activity_id,
                                            'time_from' => $date->time_from,
                                            'time_to' => $date->time_to,
                                            'date' => $date->date,
                                            'employees' => $date->employees->map(function ($employee) {
                                                $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                                $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                                return [
                                                    'id' => $employee->id,
                                                    'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                                    'employee_id' => $employee->employee_profile_id,
                                                    'employee_name' => "{$first_name} {$last_name}",
                                                    'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                                ];
                                            }),

                                        ];
                                    }),
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
                                return [
                                    'id' => $date->id,
                                    'ovt_activity_id' => $date->ovt_application_activity_id,
                                    'time_from' => $date->time_from,
                                    'time_to' => $date->time_to,
                                    'date' => $date->date,
                                    'employees' => $date->employees->map(function ($employee) {
                                        $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                        $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                        return [
                                            'id' => $employee->id,
                                            'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                            'employee_id' => $employee->employee_profile_id,
                                            'employee_name' => "{$first_name} {$last_name}",
                                            'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                        ];
                                    }),
                                ];
                            }),
                        ];
                    });

                    return response(['message' => 'Application has been sucessfully ' . $message_action, 'data' => $overtime_applications_result], Response::HTTP_CREATED);
                }
            }
        } catch (\Exception $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'updateOvertimeApplicationStatus', $th->getMessage());
            return response()->json(['message' => $th->getMessage(), 'error' => true], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resetYearlyOvertimeCredit(Request $request)
    {
        try {
            $employees = EmployeeProfile::get();
            if ($employees) {
                foreach ($employees as $employee) {
                    $employee_overtime_credits = EmployeeOvertimeCredit::where('employee_profile_id', '=', $employee->id)->get();
                    $totalLeaveCredits = $employee_overtime_credits->mapToGroups(function ($credit) {
                        return [$credit->operation => $credit->credit_value];
                    })->map(function ($operationCredits, $operation) {
                        return $operation === 'add' ? $operationCredits->sum() : -$operationCredits->sum();
                    })->sum();

                    $employeeCredit = new EmployeeOvertimeCredit();
                    $employeeCredit->employee_profile_id = $employee->id;
                    $employeeCredit->operation = "deduct";
                    $employeeCredit->reason = "Yearly Leave Credits";
                    $employeeCredit->credit_value = $totalLeaveCredits;
                    $employeeCredit->date = date('Y-m-d');
                    $employeeCredit->save();
                }
            }
            return response()->json(['data' => $employeeCredit], Response::HTTP_OK);
        } catch (\Exception $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'resetYearlyOvertimeCredit', $th->getMessage());
            return response()->json(['message' => $th->getMessage(), 'error' => true], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function printOvertimeForm($id)
    {
        try {

            $overtime_applications = OvertimeApplication::where('id', '=', $id)
                ->first();
            if ($overtime_applications) {
                $overtime_application = OvertimeApplication::with(['employeeProfile.assignedArea.division', 'employeeProfile.personalInformation', 'logs', 'activities'])
                    ->where('id', $overtime_applications->id)->get();
                $overtime_applications_result = $overtime_application->map(function ($overtime_application) {
                    $activitiesData = $overtime_application->activities ? $overtime_application->activities : collect();
                    $datesData = $overtime_application->directDates ? $overtime_application->directDates : collect();
                    $logsData = $overtime_application->logs ? $overtime_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id', $overtime_application->employee_profile_id)->value('section_id');
                    $chief_name = null;
                    $chief_position = null;
                    $chief_code = null;
                    $head_name = null;
                    $head_position = null;
                    $head_code = null;
                    $supervisor_name = null;
                    $supervisor_position = null;
                    $supervisor_code = null;
                    if ($division) {
                        $division_name = Division::with('chief.personalInformation')->find($division);

                        if ($division_name && $division_name->chief  && $division_name->chief->personalInformation != null) {
                            $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                            $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                            $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                        }
                    }
                    if ($department) {
                        $department_name = Department::with('head.personalInformation')->find($department);
                        if ($department_name && $department_name->head  && $department_name->head->personalInformation != null) {
                            $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                            $head_position = $department_name->head->assignedArea->designation->name ?? null;
                            $head_code = $department_name->head->assignedArea->designation->code ?? null;
                        }
                    }
                    if ($section) {
                        $section_name = Section::with('supervisor.personalInformation')->find($section);
                        if ($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null) {
                            $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                            $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                            $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                        }
                    }
                    $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $overtime_application->id,
                        'reason' => $overtime_application->reason,
                        'remarks' => $overtime_application->remarks,
                        'purpose' => $overtime_application->purpose,
                        'status' => $overtime_application->status,
                        'overtime_letter' => $overtime_application->overtime_letter_of_request,
                        'employee_id' => $overtime_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}",
                        'position_code' => $overtime_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $overtime_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $overtime_application->created_at,
                        'division_head' => $chief_name,
                        'division_head_position' => $chief_position,
                        'division_head_code' => $chief_code,
                        'department_head' => $head_name,
                        'department_head_position' => $head_position,
                        'department_head_code' => $head_code,
                        'section_head' => $supervisor_name,
                        'section_head_position' => $supervisor_position,
                        'section_head_code' => $supervisor_code,
                        'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                        'date' => $overtime_application->date,
                        'time' => $overtime_application->time,
                        'logs' => $logsData->map(function ($log) {
                            $process_name = $log->action;
                            $action = "";
                            $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                            if ($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id) {
                                $action =  $process_name . ' by ' . 'Division Head';
                            } else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id) {
                                $action =  $process_name . ' by ' . 'Supervisor';
                            } else {
                                $action =  $process_name . ' by ' . $first_name . ' ' . $last_name;
                            }

                            $date = $log->date;
                            $formatted_date = Carbon::parse($date)->format('M d,Y');
                            return [
                                'id' => $log->id,
                                'overtime_application_id' => $log->overtime_application_id,
                                'action_by' => "{$first_name} {$last_name}",
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'activities' => $activitiesData->map(function ($activity) {
                            return [
                                'id' => $activity->id,
                                'overtime_application_id' => $activity->overtime_application_id,
                                'name' => $activity->name,
                                'quantity' => $activity->quantity,
                                'man_hour' => $activity->man_hour,
                                'period_covered' => $activity->period_covered,
                                'dates' => $activity->dates->map(function ($date) {
                                    return [
                                        'id' => $date->id,
                                        'ovt_activity_id' => $date->ovt_application_activity_id,
                                        'time_from' => $date->time_from,
                                        'time_to' => $date->time_to,
                                        'date' => $date->date,
                                        'employees' => $date->employees->map(function ($employee) {
                                            $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                            $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                            return [
                                                'id' => $employee->id,
                                                'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                                'employee_id' => $employee->id,
                                                'employee_name' => "{$first_name} {$last_name}",
                                                'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                            ];
                                        }),

                                    ];
                                }),
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
                            return [
                                'id' => $date->id,
                                'ovt_activity_id' => $date->ovt_application_activity_id,
                                'time_from' => $date->time_from,
                                'time_to' => $date->time_to,
                                'date' => $date->date,
                                'employees' => $date->employees->map(function ($employee) {
                                    $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                    $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                    return [
                                        'id' => $employee->id,
                                        'ovt_employee_id' => $employee->ovt_application_datetime_id,
                                        'employee_id' => $employee->id,
                                        'employee_name' => "{$first_name} {$last_name}",
                                        'position' => $employee->employeeProfile->assignedArea->designation->name ?? null
                                    ];
                                }),
                            ];
                        }),
                    ];
                });
                $singleArray = array_merge(...$overtime_applications_result);
                return view('leave_from.leave_application_form', $singleArray);
            }
        } catch (\Exception $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'printOvertimeForm', $th->getMessage());
            return response()->json(['message' => $th->getMessage(), 'error' => true], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
