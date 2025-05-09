<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Helpers\Helpers;
use App\Http\Requests\AuthPinApprovalRequest;
use App\Models\EmployeeSchedule;
use App\Models\LeaveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveTypeRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\EmployeeLeaveCredit;
use App\Http\Resources\EmployeeLeaveCreditResource;
use App\Http\Resources\LeaveTypeResource;
use App\Models\EmployeeLeaveCredit as ModelsEmployeeLeaveCredit;
use App\Models\LeaveAttachment;
use App\Models\LeaveTypeLog;
use App\Models\LeaveTypeRequirement;
use App\Models\Requirement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Metadata\Api\Requirements;

class LeaveTypeController extends Controller
{

    public function index(Request $request)
    {
        try {
            $leave_types = LeaveType::all();

            return response()->json([
                'data' => LeaveTypeResource::collection($leave_types),
                'message' => 'Retrieve all leave types records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function hrmoLeaveTypes(Request $request)
    {
        try {
            $leave_types = LeaveType::get();

            return response()->json([
                'data' => LeaveTypeResource::collection($leave_types),
                'message' => 'Retrieve all leave types records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retrieve list of leaveType with employee current credit status only with non-special leave.
     * */
    public function leaveTypeOptionWithEmployeeCreditsRecord(Request $request)
    {
        try {
            $employee_profile = $request->user;

            if (!$employee_profile) {
                return response()->json(['message' => 'Unauthorized.'], Response::HTTP_NOT_FOUND);
            }

            $leave_types = LeaveType::where('is_other', false)->get();
            $result_data = [];

            foreach ($leave_types as $leave_type) {
                $requirements = $leave_type->requirements()->get();

                if ($leave_type->is_special === 1) {
                    $leave_type['total_credits'] = null;
                    continue;
                }
                $leave_type['total_credits'] = ModelsEmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)
                    ->where('leave_type_id', $leave_type->id)
                    ->first();

                $final_date = null;
                $tomorrow = Carbon::tomorrow();


                if ($leave_type->file_after === null && $leave_type->file_before !== null) {
                    $hrmo_officer = Helpers::getHrmoOfficer();
                    $schedules = EmployeeSchedule::select("s.date")->join('schedules as s', 's.id', 'employee_profile_schedule.schedule_id')
                        ->whereDate('s.date', '>=', $tomorrow)
                        ->where('employee_profile_schedule.employee_profile_id', $hrmo_officer)
                        ->limit($leave_type->file_before)->get();

                    if (count($schedules) > 0) {
                        $last_schedule = $schedules->last();
                        $final_date = $last_schedule ? Carbon::parse($last_schedule->date)->format('Y-m-d') : null;
                    }
                    // else {
                    //     // If there are no schedules, set the final date to 5 days from tomorrow
                    //     $final_date = Carbon::parse($tomorrow)->addDays(10)->format('Y-m-d');
                    // }
                }

                if ($leave_type->file_after !== null && $leave_type->code !== 'SL') {
                    $schedules = EmployeeSchedule::select("s.date")->join('schedules as s', 's.id', 'employee_profile_schedule.schedule_id')
                        ->where('employee_profile_schedule.employee_profile_id', $employee_profile->id)
                        ->whereDate('s.date', '<=', Carbon::now())
                        ->orderByDesc('s.date')->limit($leave_type->file_after)->get();

                    if (count($schedules) > 0) {
                        $last_schedule = $schedules->last();
                        $final_date = $last_schedule ? Carbon::parse($last_schedule->date)->format('Y-m-d') : null;
                    }
                }

                $result_data[] = [
                    'value' => $leave_type->id,
                    'label' => $leave_type->name,
                    'description' => $leave_type->description,
                    'period' => $leave_type->period,
                    'file_date' => $leave_type->file_date,
                    'month_value' => $leave_type->month_value,
                    'annual_credit' => $leave_type->annual_credit,
                    'is_active' => $leave_type->is_active,
                    'is_special' => $leave_type->is_special,
                    'is_country' => $leave_type->is_country,
                    'is_illness' => $leave_type->is_illness,
                    'is_study' => $leave_type->is_study,
                    'is_days_recommended' => $leave_type->is_days_recommended,
                    'created_at' => $leave_type->created_at,
                    'updated_at' => $leave_type->updated_at,
                    'allowed_date' => $final_date,
                    'total_credits' => $leave_type->total_credits ? [
                        'id' => $leave_type->total_credits->id,
                        'employee_profile_id' => $leave_type->total_credits->employee_profile_id,
                        'leave_type_id' => $leave_type->total_credits->leave_type_id,
                        'total_leave_credits' => $leave_type->total_credits->total_leave_credits,
                        'created_at' => $leave_type->total_credits->created_at,
                        'updated_at' => $leave_type->total_credits->updated_at,
                    ] : null,
                    'requirements' => $requirements ?? [],
                ];
            }


            return response()->json([
                'data' => $result_data,
                'message' => 'Retrieve all leave types records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function hrmoLeaveTypeOptionWithEmployeeCreditsRecord(Request $request)
    {
        try {
            $employee_profile = $request->user;

            if (!$employee_profile) {
                return response()->json(['message' => 'Unauthorized.'], Response::HTTP_NOT_FOUND);
            }

            $leave_types = LeaveType::all();

            $result_data = [];

            foreach ($leave_types as $leave_type) {
                $requirements = $leave_type->requirements()->get();

                if ($leave_type->is_special === 1) {
                    $leave_type['total_credits'] = null;
                    continue;
                }
                $leave_type['total_credits'] = ModelsEmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)
                    ->where('leave_type_id', $leave_type->id)
                    ->first();

                $final_date = null;
                $tomorrow = Carbon::tomorrow();


                if ($leave_type->file_after === null && $leave_type->file_before !== null) {
                    $hrmo_officer = Helpers::getHrmoOfficer();
                    $schedules = EmployeeSchedule::select("s.date")->join('schedules as s', 's.id', 'employee_profile_schedule.schedule_id')
                        ->whereDate('s.date', '>=', $tomorrow)
                        ->where('employee_profile_schedule.employee_profile_id', $hrmo_officer)
                        ->limit($leave_type->file_before)->get();

                    if (count($schedules) > 0) {
                        $last_schedule = $schedules->last();
                        $final_date = $last_schedule ? Carbon::parse($last_schedule->date)->format('Y-m-d') : null;
                    }
                    // else {
                    //     // If there are no schedules, set the final date to 5 days from tomorrow
                    //     $final_date = Carbon::parse($tomorrow)->addDays(10)->format('Y-m-d');
                    // }
                }

                if ($leave_type->file_after !== null && $leave_type->code !== 'SL') {
                    $schedules = EmployeeSchedule::select("s.date")->join('schedules as s', 's.id', 'employee_profile_schedule.schedule_id')
                        ->where('employee_profile_schedule.employee_profile_id', $employee_profile->id)
                        ->whereDate('s.date', '<=', Carbon::now())
                        ->orderByDesc('s.date')->limit($leave_type->file_after)->get();

                    if (count($schedules) > 0) {
                        $last_schedule = $schedules->last();
                        $final_date = $last_schedule ? Carbon::parse($last_schedule->date)->format('Y-m-d') : null;
                    }
                }

                $result_data[] = [
                    'value' => $leave_type->id,
                    'label' => $leave_type->name,
                    'description' => $leave_type->description,
                    'period' => $leave_type->period,
                    'file_date' => $leave_type->file_date,
                    'month_value' => $leave_type->month_value,
                    'annual_credit' => $leave_type->annual_credit,
                    'is_active' => $leave_type->is_active,
                    'is_special' => $leave_type->is_special,
                    'is_country' => $leave_type->is_country,
                    'is_illness' => $leave_type->is_illness,
                    'is_study' => $leave_type->is_study,
                    'is_days_recommended' => $leave_type->is_days_recommended,
                    'created_at' => $leave_type->created_at,
                    'updated_at' => $leave_type->updated_at,
                    'allowed_date' => $final_date,
                    'total_credits' => $leave_type->total_credits ? [
                        'id' => $leave_type->total_credits->id,
                        'employee_profile_id' => $leave_type->total_credits->employee_profile_id,
                        'leave_type_id' => $leave_type->total_credits->leave_type_id,
                        'total_leave_credits' => $leave_type->total_credits->total_leave_credits,
                        'created_at' => $leave_type->total_credits->created_at,
                        'updated_at' => $leave_type->total_credits->updated_at,
                    ] : null,
                    'requirements' => $requirements ?? [],
                ];
            }


            return response()->json([
                'data' => $result_data,
                'message' => 'Retrieve all leave types records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeLeaveCredit(Request $request)
    {
        try {
            $employee_leave_credit = $request->user->leaveCredits;

            return response()->json([
                'data' => new EmployeeLeaveCreditResource($employee_leave_credit),
                'message' => 'Retrieve employee leave credit'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => ''], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(LeaveTypeRequest $request)
    {
        try {
            $employee_profile = $request->user;

            if (!$employee_profile) {
                return response()->json(['message' => 'Unauthorized.'], Response::HTTP_FORBIDDEN);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (is_bool($value)) {
                    $cleanData[$key] = $value === 1 ? true : false;
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

            $code = Helpers::generateCodeFromName($cleanData['name']);
            $month_value = $cleanData['leave_credit_year'] / 12;

            $cleanData['code'] = $code;
            $cleanData['month_value'] = $month_value;
            $cleanData['annual_credit'] = $cleanData['leave_credit_year'];
            $cleanData['is_active'] = true;
            $cleanData['is_other'] = $cleanData['is_other'];
            $cleanData['is_special'] = $cleanData['is_special'];
            $cleanData['republic_act'] = $cleanData['republic_act'];
            $cleanData['description'] = $cleanData['description'];
            $cleanData['period'] = $cleanData['period'];
            $cleanData['file_date'] = $cleanData['file_date'];
            $cleanData['file_before'] = $cleanData['file_before'];
            $cleanData['is_country'] = $cleanData['is_country'];
            $cleanData['is_illness'] = $cleanData['is_illness'];
            $cleanData['is_study'] = $cleanData['is_study'];
            $cleanData['is_days_recommended'] = $cleanData['is_days_recommended'];



            $leave_type = LeaveType::create($cleanData);
            if (!$leave_type->is_special) {

                $employeesWithCredits = ModelsEmployeeLeaveCredit::groupBy('employee_profile_id')
                    ->get(['employee_profile_id']);


                foreach ($employeesWithCredits as $employee) {
                    ModelsEmployeeLeaveCredit::create([
                        'employee_profile_id' => $employee->employee_profile_id, // Correctly accessing the property
                        'leave_type_id' => $leave_type->id,
                        'total_leave_credits' => 0,
                        'used_leave_credits' => 0
                    ]);
                }
            }
            if ($request->requirements) {
                foreach ($cleanData['requirements'] as $value) {
                    LeaveTypeRequirement::create([
                        'leave_type_id' => $leave_type->id,
                        'leave_requirement_id' => $value
                    ]);
                }
            }

            if ($request->attachments) {
                foreach ($request->file('attachments') as $key => $file) {
                    $fileName = $file->getClientOriginalName();
                    $size = filesize($file);
                    $file_name_encrypted = Helpers::checkSaveFile($file, '/requirements');

                    LeaveAttachment::create([
                        'leave_type_id' => $leave_type->id,
                        'file_name' => $fileName,
                        'path' => $file_name_encrypted,
                        'size' => $size,
                    ]);
                }
            }

            LeaveTypeLog::create([
                'leave_type_id' => $leave_type->id,
                'action_by' => $employee_profile->id,
                'action' => 'Add'
            ]);

            return response()->json([
                'data' => new LeaveTypeResource($leave_type),
                'message' => 'Leave type record created successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $leave_types = LeaveType::find($id);

            return response()->json([
                'data' => new LeaveTypeResource($leave_types),
                'message' => 'Retrieve all leave types records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $employee_profile = $request->user;

            if (!$employee_profile) {
                return response()->json(['message' => 'Unauthorized.'], Response::HTTP_FORBIDDEN);
            }

            $leave_type = LeaveType::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($key === 'leave_type_requirements') continue;

                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (is_bool($value)) {
                    $cleanData[$key] = $value ? true : false;
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

            $requirements_array = $request->requirements;
            foreach ($requirements_array as $key => $value) {
                if ($value === null) {
                    $sql = LeaveTypeRequirement::where('leave_type_id', $leave_type->id)->first();
                    if ($sql) {
                        $sql->delete();
                    }
                } else if ($value) {
                    $leave_requirements = LeaveTypeRequirement::where('id', $value)->first();

                    if (!$leave_requirements) {
                        $requirements = Requirement::where('id', $value)->first();
                        $leave_requirements = $leave_type->requirements()->attach($requirements);
                    } else {

                        $sql = Requirement::where('id', $leave_requirements->leave_requirement_id)->pluck('id')->toArray();
                        $leave_type->requirements()->sync($sql);
                    }
                }
            }

            $leave_type->update($cleanData);

            /**
             * Only if client side pass a value of update_leave_type_requirements
             * ex.
             * [
             *  {
             *      id: 1,
             *      leave_requirement_id: 4
             *  }
             * ]
             */
            if ($request->update_leave_type_requirements !== null) {
                foreach ($request->leave_type_requirements as $key => $value) {
                    LeaveTypeRequirement::find($value->id)->update([
                        'leave_type_id' => $leave_type->id,
                        'leave_requirement_id' => $value->leave_requirement_id
                    ]);
                }
            }

            LeaveTypeLog::create([
                'leave_type_id' => $leave_type->id,
                'action_by' => $employee_profile->id,
                'action' => 'Update'
            ]);

            return response()->json([
                'data' => new LeaveTypeResource($leave_type),
                'message' => $leave_type->name . ' updated.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deactivateLeaveTypes($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_type = LeaveType::find($id);
            $leave_type->update(['is_active' => 0]);

            return response()->json([
                'data' => new LeaveTypeResource($leave_type),
                'message' => 'Leave type record deactivated successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reactivateLeaveTypes($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_type = LeaveType::find($id);
            $leave_type->update(['is_active' => true]);

            return response()->json([
                'data' => new LeaveTypeResource($leave_type),
                'message' => 'Leave type record reactivated successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_type = LeaveType::find($id);

            if (count($leave_type->leaveApplications) > 0) {
                return response()->json(['message' => "Other record is using this leave type deletion is prohibited."], Response::HTTP_BAD_REQUEST);
            }

            if (count($leave_type->leaveTypeCredit) > 0) {
                return response()->json(['message' => "Other record is using this leave type deletion is prohibited."], Response::HTTP_BAD_REQUEST);
            }

            $leave_type->delete();

            return response()->json([
                'message' => 'Leave type record deleted successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
