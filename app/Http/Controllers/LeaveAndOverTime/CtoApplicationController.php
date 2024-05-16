<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Helpers\Helpers;
use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Resources\EmployeeOvertimeCreditResource;
use App\Models\CtoApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\CtoApplicationRequest;
use App\Http\Resources\CtoApplicationResource;
use App\Models\CtoApplicationLog;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeOvertimeCreditLog;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;

class CtoApplicationController extends Controller
{

    public function index(Request $request)
    {
        try {
            $employee_profile   = $request->user;
            $employee_area      = $employee_profile->assignedArea->findDetails();
            $recommending = ["for recommending approval", "for approving approval", "approved", "declined by recommending officer"];
            $approving = ["for approving approval", "approved", "declined by approving officer"];
            $position = $employee_profile->position();
            $employeeId = $employee_profile->id;

            /**
             * Division Head [approving, recommending] - applications of assigned area
             *  - recommending => [for recommending approval, for approving approval, approved, declined]
             *  - approving => [ for approving approval, approved, declined]
             *
             * Department Head [recommending] - applications of assigned area
             *  - recommending => [for recommending approval, for approving approval, approved, declined]
             *
             * Section Supervisor [recommending] - applications of assigned area
             *  - recommending => [for recommending approval, for approving approval, approved, declined]
             *
             */


            /** FOR NORMAL EMPLOYEE */
            if ($employee_profile->position() === null) {
                $cto_application = CtoApplication::where('employee_profile_id', $employee_profile->id)->get();

                return response()->json([
                    'data' => CtoApplicationResource::collection($cto_application),
                    'message' => 'Retrieved all CTO application'
                ], Response::HTTP_OK);
            }
            // if ($employee_area->sector['Section'] === 'HRMO') {
            //     return response()->json([
            //         'data' => CtoApplicationResource::collection(CtoApplication::all()),
            //         'message' => 'Retrieved all offical business application'
            //     ], Response::HTTP_OK);
            // }

            $cto_application = CtoApplication::select('cto_applications.*')
                ->where(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('cto_applications.status', $recommending)
                        ->where('cto_applications.recommending_officer', $employeeId);
                })
                ->orWhere(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('cto_applications.status', $approving)
                        ->where('cto_applications.approving_officer', $employeeId);
                })
                ->groupBy(
                    'id',
                    'date',
                    'applied_credits',
                    'is_am',
                    'is_pm',
                    'status',
                    'purpose',
                    'recommending_officer',
                    'approving_officer',
                    'remarks',
                    'employee_profile_id',
                    'created_at',
                    'updated_at',
                )
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => CtoApplicationResource::collection($cto_application),
                'message' => 'Retrieved all cto application'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function CtoApplicationUnderSameArea(Request $request)
    {
        try {
            $employee_profile   = $request->user;
            $position = $employee_profile->position();
            if ($position !== null) {
                $assigned_area = $employee_profile->assignedArea;

                $employees_in_same_area = EmployeeProfile::where(function ($query) use ($assigned_area) {
                    if ($assigned_area->division_id !== null) {
                        $query->whereHas('assignedArea', function ($q) use ($assigned_area) {
                            $q->where('division_id', $assigned_area->division_id);
                        });
                    } elseif ($assigned_area->department_id !== null) {
                        $query->whereHas('assignedArea', function ($q) use ($assigned_area) {
                            $q->where('department_id', $assigned_area->department_id);
                        });
                    } elseif ($assigned_area->section_id !== null) {
                        $query->whereHas('assignedArea', function ($q) use ($assigned_area) {
                            $q->where('section_id', $assigned_area->section_id);
                        });
                    }
                })->has('ctoApplications')->with('ctoApplications')->get();

                $cto_applications_resource = CtoApplicationResource::collection($employees_in_same_area->pluck('ctoApplications')->flatten());
                return response()->json([
                    'data' => $cto_applications_resource,
                    'message' => 'Retrieved CTO applications in the same area'
                ], Response::HTTP_OK);
            }
        } catch (\Throwable $th) {

            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(Request $request)
    {
        try {
            $user = $request->user;
            $sql = CtoApplication::where('employee_profile_id', $user->id)->get();
            $currentYear = Carbon::now()->year;
            $usedCreditThisYear = (float) CtoApplication::where('employee_profile_id', $user->id)
            ->where(function ($query) {
                $query->where('status', 'approved')
                      ->orWhere('status', 'for recommending approval')
                      ->orWhere('status', 'for approving approval');
            })
                ->whereYear('created_at', $currentYear)
                ->sum('applied_credits');

            $employeeCredit = EmployeeOvertimeCredit::where('employee_profile_id', $user->id)->get();
            return response()->json([
                'data' => CtoApplicationResource::collection($sql),
                'employee_credit' => EmployeeOvertimeCreditResource::collection($employeeCredit),
                'used_credit_this_year' => $usedCreditThisYear
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {

            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approved($id, AuthPinApprovalRequest $request)
    {
        try {
            $employee_profile = $request->user;
            $data = CtoApplication::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $status     = null;
            $log_action = null;

            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            if ($request->status === 'approved') {
                switch ($data->status) {
                    case 'for recommending approval':
                        $status = 'for approving approval';
                        $log_action = 'Approved by Recommending Officer';
                        break;

                    case 'for approving approval':
                        $status = 'approved';
                        $log_action = 'Approved by Approving Officer';
                        break;

                        // default:
                        //     $status = 'declined';
                        //     $log_action = 'Request Declined';
                        // break;
                }
            } else if ($request->status === 'declined') {
                $cto_application_recommending = $data->recommending_officer;
                $cto_application_approving = $data->approving_officer;


                if ($employee_profile->id === $cto_application_recommending) {
                    $status = 'declined by recommending officer';
                } else if ($employee_profile->id === $cto_application_approving) {
                    $status = 'declined by approving officer';
                }
                $log_action = 'Request Declined';
            }
            CtoApplicationLog::create([
                'action_by' => $employee_profile->id,
                'cto_application_id' => $data->id,
                'action' => $log_action,
            ]);

            $data->update(['status' => $status, 'remarks' => $request->remarks === 'null' || !$request->remarks ? null : $request->remarks]);

            $employeeCredit = EmployeeOvertimeCredit::where('employee_profile_id', $data->employee_profile_id)
            ->where('is_expired', 0)
            ->orderBy('valid_until', 'asc')
            ->get();


            $currentYear = Carbon::now()->year;

            $usedCreditThisYear = (float) CtoApplication::where('employee_profile_id', $data->employee_profile_id)
            ->where(function ($query) {
                $query->where('status', 'approved')
                      ->orWhere('status', 'for recommending approval')
                      ->orWhere('status', 'for approving approval');
            })
                ->whereYear('created_at', $currentYear)
                ->sum('applied_credits');

            return response()->json([
                'data' => CtoApplicationResource::collection(CtoApplication::where('id', $data->id)->get()),
                'employee_credit' => EmployeeOvertimeCreditResource::collection($employeeCredit),
                'used_credit_this_year' => $usedCreditThisYear,
                'message' => $log_action,
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {

            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(CtoApplicationRequest $request)
    {
        try {


            $employee_profile = $request->user;
            $cto_applications = [];

            $assigned_area = $employee_profile->assignedArea->findDetails();
            $approving_officer = Helpers::getDivHead($assigned_area);
            $hrmo_officer = Helpers::getHrmoOfficer();

            $reason = [];
            $failed = [];

            $employee_profile = $request->user;
            $employeeId = $employee_profile->id;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($employee_profile['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            if (!$employee_profile) {
                return response()->json(['message' => 'Unauthorized.'], Response::HTTP_FORBIDDEN);
            }

            if ($hrmo_officer === null || $approving_officer === null) {
                return response()->json(['message' => 'No recommending officer and/or supervising officer assigned.'], Response::HTTP_FORBIDDEN);
            }
            $date = Carbon::parse($request->date);
            $checkSchedule = Helpers::hasSchedule($date, $date, $employeeId);
            if (!$checkSchedule) {
                return response()->json(['message' => "You don't have a schedule within the specified date range."], Response::HTTP_FORBIDDEN);
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

                if (is_array($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            foreach (json_decode($request->cto_applications) as $key => $value) {


                $first_valid_until = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)
                    ->where('is_expired', false)
                    ->orderBy('valid_until')
                    ->value('valid_until');

                // Check if the applied date is after the first valid until date
                if (Carbon::parse($value->date) > Carbon::parse($first_valid_until)) {
                    // Get the next valid until date
                    $next_valid_until = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)
                        ->where('is_expired', false)
                        ->where('valid_until', '>', $first_valid_until)
                        ->orderBy('valid_until')
                        ->value('valid_until');

                    // Check if there's a next valid until date
                    if ($next_valid_until) {
                        // Check if the applied date is within the valid until of the next valid until
                        if (Carbon::parse($value->date) > Carbon::parse($next_valid_until)) {
                            // If the applied date is after the next valid until, mark the application as failed
                            $failed[] = $value;
                            $reason[] = 'Applied date is after the next valid until date.';
                            continue;
                        }
                        // Retrieve the employee credit for the next valid until date
                        $employee_credit = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)
                            ->where('valid_until', $next_valid_until)
                            ->first();
                    } else {
                        // If no next valid until date is found, mark the application as failed
                        $failed[] = $value;
                        $reason[] = 'No more valid overtime credits available.';
                        continue;
                    }
                } else {
                    // Retrieve the employee credit for the first valid until date
                    $employee_credit = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)
                        ->where('valid_until', $first_valid_until)
                        ->first();
                }
                $currentDate = Carbon::now();
                $overtimeCredits = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)->get();
                $allCreditsUsedUp = true;


                foreach ($overtimeCredits as $credit) {
                    // Check if the credit is still valid and has used hours remaining
                    if ($currentDate->lte(Carbon::parse($credit->valid_until)) && $credit->earned_credit_by_hour > 0) {
                        $allCreditsUsedUp = false;
                        break;
                    }
                }

                if ($allCreditsUsedUp) {
                    $failed[] = $value;
                    $reason[] = 'No overtime credits available.';
                    continue;
                }

                $totalEarnedCredits = $overtimeCredits->sum('earned_credit_by_hour');
                $appliedCredits = $value->applied_credits;
                if ($appliedCredits > $totalEarnedCredits) {

                    $failed[] = $value;
                    $reason[] = 'Insufficient overtime credits available.';
                    continue;
                }

                $date = Carbon::parse($value->date);
                $employeeId = $employee_profile->id;
                $overlapExists = Helpers::hasOverlappingCTO($date, $employeeId);

                if ($overlapExists) {
                    return response()->json(['message' => 'You already have an application for the same dates.'], Response::HTTP_FORBIDDEN);
                } else {

                    $cleanData['employee_profile_id'] = $employee_profile->id;
                    $cleanData['date'] = $value->date;
                    $cleanData['applied_credits'] = $value->applied_credits;
                    $cleanData['is_am'] = $value->is_am;
                    $cleanData['is_pm'] = $value->is_pm;
                    $cleanData['purpose'] = $value->purpose;
                    // $cleanData['remarks'] = $value->remarks;
                    $cleanData['status'] = 'for recommending approval';
                    $cleanData['recommending_officer'] = $hrmo_officer;
                    $cleanData['approving_officer'] = $approving_officer;

                    $credits = $value->applied_credits;
                    $cto_application = CtoApplication::create($cleanData);

                    $current_overtime_credit = $employee_credit->earned_credit_by_hour;
                    $earned_credit = $employee_credit->earned_credit_by_hour;
                    $used_credit = $employee_credit->used_credit_by_hour;
                    // $employee_credit->where('valid_until', $first_valid_until)->update(['earned_credit_by_hour' => $earned_credit - $credits, 'used_credit_by_hour' => $used_credit + $credits]);
                    // $employeeCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)->get();
                    // $logs =  EmployeeOvertimeCreditLog::create([
                    //     'employee_ot_credit_id' => $employee_credit->id,
                    //     'cto_application_id' => $cto_application->id,
                    //     'action' => 'deduct',
                    //     'reason' => 'Apply',
                    //     'previous_overtime_hours' => $current_overtime_credit,
                    //     'hours' => $value->applied_credits
                    // ]);

                    $appliedCredits = $credits;
                    $remainingCredits = $appliedCredits;
                    $employeeCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)
                        ->where('is_expired', 0)
                        ->orderBy('valid_until', 'asc')
                        ->get();

                    foreach ($employeeCredit as $credit) {
                        $earnedCredit = $credit->earned_credit_by_hour;
                        $validUntil = $credit->valid_until;

                        $remainingEarnedCredit = $earnedCredit;


                        if ($remainingCredits >= $remainingEarnedCredit) {
                            $remainingCredits -= $remainingEarnedCredit;
                            $credit->update(['earned_credit_by_hour' => 0]);
                        } else {
                            $remainingEarnedCredit -= $remainingCredits;
                            $credit->update(['earned_credit_by_hour' => $remainingEarnedCredit]);
                            $remainingCredits = 0;
                        }

                        // If all applied credits are utilized, exit the loop
                        if ($remainingCredits <= 0) {
                            break;
                        }
                    }

                    CtoApplicationLog::create([
                        'action_by' => $employee_profile->id,
                        'cto_application_id' => $cto_application->id,
                        'action' => 'Applied'
                    ]);

                    $cto_applications[] = $cto_application;
                }
            }



            if (count($failed) === count(json_decode($request->cto_applications, true))) {
                return response()->json([
                    'failed' => $failed,
                    'reason' => $reason,
                    'message' => 'Failed to register all compensatory time off applications.'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (count($failed) > 0) {
                return response()->json([
                    'data' => CtoApplicationResource::collection($cto_applications),
                    'failed' => $failed,
                    'employee_credit' => EmployeeOvertimeCreditResource::collection($employeeCredit),
                    'message' => count($failed) . 'application/s failed to register.'
                ], Response::HTTP_OK);
            }

            $currentYear = Carbon::now()->year;
            $usedCreditThisYear = (float) CtoApplication::where('employee_profile_id', $employeeId)
            ->where(function ($query) {
                $query->where('status', 'approved')
                      ->orWhere('status', 'for recommending approval')
                      ->orWhere('status', 'for approving approval');
            })
                ->whereYear('created_at', $currentYear)
                ->sum('applied_credits');

            return response()->json([
                'data' => CtoApplicationResource::collection($cto_applications),
                'employee_credit' => EmployeeOvertimeCreditResource::collection($employeeCredit),
                'used_credit_this_year' => $usedCreditThisYear,
                'message' => 'Request submitted sucessfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $cto_applications = CtoApplication::find($id);

            return response()->json([
                'data' => new CtoApplicationResource($cto_applications),
                'message' => 'Retrieve compensatory application record.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function declined($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $cto_application = CtoApplication::find($id);

            $cto_application->update([
                'status' => 'Declined',
                'reason' => strip_tags($request->reason)
            ]);

            $employee_credit = EmployeeOvertimeCredit::where('employee_profile_id', $cto_application->employee_profile_id)->first();

            $current_overtime_credit = $employee_credit->earn_credit_by_hour;
            $used_credit  = $employee_credit->used_credit_by_hour;

            $employee_credit->update([
                'earn_credit_by_hour' => $current_overtime_credit +  $cto_application->applied_credits,
                'used_credit_by_hour' => $used_credit - $cto_application->applied_credits
            ]);

            EmployeeOvertimeCreditLog::create([
                'employee_ot_credit_id' => $employee_credit->id,
                'cto_application_id' => $cto_application->id,
                'action' => 'add',
                'reason' => 'Declined',
                'previous_overtime_hours' => $current_overtime_credit,
                'hours' => $cto_application->applied_credits
            ]);

            CtoApplicationLog::create([
                'action_by' => $user->id,
                'cto_application_id' => $cto_application->id,
                'action' => 'Declined'
            ]);

            $employeeCredit = EmployeeOvertimeCredit::where('employee_profile_id', $cto_application->employee_profile_id)
            ->where('is_expired', 0)
            ->orderBy('valid_until', 'asc')
            ->get();


            $currentYear = Carbon::now()->year;

            $usedCreditThisYear = (float) CtoApplication::where('employee_profile_id', $cto_application->employee_profile_id)
            ->where(function ($query) {
                $query->where('status', 'approved')
                      ->orWhere('status', 'for recommending approval')
                      ->orWhere('status', 'for approving approval');
            })
                ->whereYear('created_at', $currentYear)
                ->sum('applied_credits');

            return response()->json([
                'data' => new CtoApplicationResource($cto_application),
                'employee_credit' => EmployeeOvertimeCreditResource::collection($employeeCredit),
                'used_credit_this_year' => $usedCreditThisYear,
                'message' => 'Retrieve compensatory time off application record.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function old()
    {
        // foreach (json_decode($request->cto_applications) as $key => $value) {

        //     $employee_credit = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)->first();

        //     if ($employee_credit->earned_credit_by_hour < $value->applied_credits) {
        //         $failed[] = $value;
        //         $reason[] = 'Insufficient overtime credit.';
        //         continue;
        //     }
        //     $date = Carbon::parse($value->date);
        //     $employeeId = $employee_profile->id;
        //     $overlapExists = Helpers::hasOverlappingCTO($date, $employeeId);

        //     if ($overlapExists) {
        //         return response()->json(['message' => 'You already have an application for the same dates.'], Response::HTTP_FORBIDDEN);
        //     } else {

        //         $cleanData['employee_profile_id'] = $employee_profile->id;
        //         $cleanData['date'] = $value->date;
        //         $cleanData['applied_credits'] = $value->applied_credits;
        //         $cleanData['is_am'] = $value->is_am;
        //         $cleanData['is_pm'] = $value->is_pm;
        //         $cleanData['purpose'] = $value->purpose;
        //         $cleanData['remarks'] = $value->remarks;
        //         $cleanData['status'] = 'for recommending approval';
        //         $cleanData['recommending_officer'] = $hrmo_officer;
        //         $cleanData['approving_officer'] = $approving_officer;

        //         $credits = $value->applied_credits;
        //         $cto_application = CtoApplication::create($cleanData);

        //         $current_overtime_credit = $employee_credit->earned_credit_by_hour;
        //         $earned_credit = $employee_credit->earned_credit_by_hour;
        //         $used_credit = $employee_credit->used_credit_by_hour;
        //         $employee_credit->update(['earned_credit_by_hour' => $earned_credit - $credits, 'used_credit_by_hour' => $used_credit + $credits]);
        //         $employeeCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)->get();
        //         $logs =  EmployeeOvertimeCreditLog::create([
        //             'employee_ot_credit_id' => $employee_credit->id,
        //             'cto_application_id' => $cto_application->id,
        //             'action' => 'CTO',
        //             'previous_overtime_hours' => $current_overtime_credit,
        //             'hours' => $value->applied_credits
        //         ]);

        //         CtoApplicationLog::create([
        //             'action_by' => $employee_profile->id,
        //             'cto_application_id' => $cto_application->id,
        //             'action' => 'Applied'
        //         ]);

        //         $cto_applications[] = $cto_application;
        //     }
        // }

    }

    public function updateCredit(Request $request)
    {
        try {
            $employeeId = $request->employee_id;
            $validUntil = $request->valid_until;
            $creditValue = $request->credit_value;
            $validUntilDate = date('Y-m-d', strtotime($request->valid_until));

            $employee_profile = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);
            if ($employee_profile['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $existingCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employeeId)
                ->where('valid_until', $validUntilDate)
                ->first();

            if ($existingCredit) {
                $existingCredit->earned_credit_by_hour += $creditValue;
                $existingCredit->save();
            } else {

                $newCredit = EmployeeOvertimeCredit::create([
                    'employee_profile_id' => $employeeId,
                    'earned_credit_by_hour' => $creditValue,
                    'used_credit_by_hour' => '0',
                    'max_credit_monthly' => '40',
                    'max_credit_annual' => '120',
                    'valid_until' => $validUntil,
                    'is_expired' => '0',
                ]);
                EmployeeOvertimeCreditLog::create([
                    'employee_ot_credit_id' => $employeeId,
                    'action' => 'add',
                    'reason' => 'Update Credit',
                    'hours' => $creditValue
                ]);

                $existingCredit = $newCredit;
            }

            $overtimeCredits = EmployeeOvertimeCredit::with(['employeeProfile.personalInformation'])->where('employee_profile_id', $employeeId)->get();
            $currentYearBalance = 0;
            $currentYearValidUntil = null;
            $nextYearBalance = 0;
            $nextYearValidUntil = null;
            $overallTotalBalance = 0;

            foreach ($overtimeCredits as $credit) {
                $employeeDetails = $credit->employeeProfile->personalInformation->name();
                if (!$credit->is_expired) {
                    $validUntil = Carbon::parse($credit->valid_until);
                    $year = $validUntil->year;
                    $overallTotalBalance += $credit->earned_credit_by_hour;
                    if ($year == Carbon::now()->year) {
                        $currentYearBalance += $credit->earned_credit_by_hour;
                        $currentYearValidUntil = $validUntil->toDateString();
                    } elseif ($year == Carbon::now()->year + 1) {
                        $nextYearBalance += $credit->earned_credit_by_hour;
                        $nextYearValidUntil = $validUntil->toDateString();
                    }
                }
            }

            $employeeResponse = [
                'id' => $credit->employee_profile_id,
                'name' => $employeeDetails,
                'employee_id' => $existingCredit->employeeProfile->employee_id,
                'credits' => [
                    'current_year_balance' => $currentYearBalance,
                    'current_valid_until' => $currentYearValidUntil,
                    'next_year_balance' => $nextYearBalance,
                    'next_year_valid_until' => $nextYearValidUntil,
                    'overall_total_balance' => $overallTotalBalance,
                ],
            ];

            return response()->json([
                'data' => $employeeResponse,
                'message' =>  'Leave credits updated successfully'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeCreditLog($id)
    {
        try {
            $employeeCredits = EmployeeOvertimeCredit::with(['logs', 'employeeProfile'])->where('employee_profile_id', $id)->get();
            $allLogs = [];
            $employeeName = null;
            $employeePosition = null;
            $currentYear = Carbon::now()->year;
            $nextYear = $currentYear + 1;
            $totalCreditsEarnedThisMonth = 0;
            $totalCreditsEarnedThisYear = 0;
            $totalCreditsEarnedNextYear = 0;
            $totalCreditsExpiringThisYear = 0;
            $totalCreditsExpiringNextYear = 0;
            $totalUsableCredits=0;
            foreach ($employeeCredits as $employeeCredit) {

                if (!$employeeName) {
                    $employeeName = $employeeCredit->employeeProfile->name();
                    $employeeJobPosition = $employeeCredit->employeeProfile->findDesignation()->code;
                    $employeePosition = $employeeCredit->employeeProfile->employmentType->name;
                    $employee_assign_area = $employeeCredit->employeeProfile->assignedArea->findDetails();
                }
                 $validUntilYear = Carbon::parse($employeeCredit->valid_until)->year;

                if ($validUntilYear === $currentYear) {
                    $totalCreditsExpiringThisYear += $employeeCredit->earned_credit_by_hour;
                } elseif ($validUntilYear === $nextYear) {
                    $totalCreditsExpiringNextYear += $employeeCredit->earned_credit_by_hour;
                }
                $totalUsableCredits =  $totalCreditsExpiringThisYear +  $totalCreditsExpiringNextYear ;

                $logs = $employeeCredit->logs;

                foreach ($logs as $log) {

                    if ($log->action === 'add') {

                        if (Carbon::parse($log->created_at)->format('Y-m') === Carbon::now()->format('Y-m')) {
                            $totalCreditsEarnedThisMonth += $log->hours;
                        }

                        if (Carbon::parse($log->created_at)->format('Y') === Carbon::now()->format('Y')) {
                            $totalCreditsEarnedThisYear += $log->hours;
                        }

                        if (Carbon::parse($log->created_at)->format('Y') === Carbon::now()->addYear()->format('Y')) {
                            return $totalCreditsEarnedNextYear += $log->hours;
                        }
                    }
                    $allLogs[] = [
                        'reason' => $log->reason,
                        'action' => $log->action,
                        'previous_overtime_hours' => $log->previous_overtime_hours ?? 0,
                        'hours' => $log->hours,
                        'remaining' => $log->previous_overtime_hours !== null ? $log->previous_overtime_hours - $log->hours : $log->hours,
                        'created_at' =>  $log->created_at,
                    ];
                }
            }
            $response = [
                'employee_name' => $employeeName,
                'employee_job' => $employeeJobPosition,
                'employee_position' => $employeePosition,
                'employee_area' => $employee_assign_area,
                'earned_this_month' => $totalCreditsEarnedThisMonth,
                'earned_this_year' => $totalCreditsEarnedThisYear,
                'earned_next_year' => $totalCreditsEarnedNextYear,
                'total_usable_credits' =>  $totalUsableCredits,
                'expiring_this_year' => $totalCreditsExpiringThisYear,
                'expiring_next_year' => $totalCreditsExpiringNextYear,
                'logs' => $allLogs,
            ];
            // $response =array_merge($employeeDetails,$allLogs);
            return ['data' => $response];
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEmployees()
    {
        try {

            $overtimeCredits = EmployeeOvertimeCredit::with(['employeeProfile.personalInformation'])->get()->groupBy('employee_profile_id');
            $response = [];
            foreach ($overtimeCredits as $employeeProfileId => $credits) {
                $employeeDetails = $credits->first()->employeeProfile->personalInformation->name();

                $currentYearBalance = 0;
                $currentYearValidUntil = null;
                $nextYearBalance = 0;
                $nextYearValidUntil = null;
                $overallTotalBalance = 0;

                foreach ($credits as $credit) {
                    if (!$credit->is_expired) {
                        $validUntil = Carbon::parse($credit->valid_until);
                        $year = $validUntil->year;
                        $overallTotalBalance += $credit->earned_credit_by_hour;
                        if ($year == Carbon::now()->year) {
                            $currentYearBalance += $credit->earned_credit_by_hour;
                            $currentYearValidUntil = $validUntil->toDateString();
                        } elseif ($year == Carbon::now()->year + 1) {
                            $nextYearBalance += $credit->earned_credit_by_hour;
                            $nextYearValidUntil = $validUntil->toDateString();
                        }
                    }
                }

                $employeeResponse = [
                    'id' => $employeeProfileId,
                    'name' => $employeeDetails,
                    'employee_id' => $credits->first()->employeeProfile->employee_id,
                    'credits' => [
                        'current_year_balance' => $currentYearBalance,
                        'current_valid_until' => $currentYearValidUntil,
                        'next_year_balance' => $nextYearBalance,
                        'next_year_valid_until' => $nextYearValidUntil,
                        'overall_total_balance' => $overallTotalBalance,
                    ],
                ];

                $response[] = $employeeResponse;
            }

            return ['data' => $response];
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
