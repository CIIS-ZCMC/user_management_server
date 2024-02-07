<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveType;
use App\Models\Section;
use Carbon\Carbon;
use App\Helpers\Helpers;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\LeaveApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveApplicationRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\LeaveApplicationResource;
use App\Models\AssignArea;
use App\Models\Division;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeLeaveCreditLogs;
use App\Models\EmployeeProfile;
use App\Models\LeaveApplicationLog;
use App\Models\LeaveApplicationRequirement;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class LeaveApplicationController extends Controller
{
    public function index(Request $request)
    {
        try {
  
            $employee_profile = $request->user;

            /**
             * HR division
             * Only newly applied leave application
             */
           
            if (Helpers::getHrmoOfficer() === $employee_profile->id) {
                
                $leave_applications = LeaveApplication::where('hrmo_officer', $employee_profile->id)->get();

                return response()->json([
                    'data' => LeaveApplicationResource::collection($leave_applications),
                    'message' => 'Retrieve all leave application records.'
                ], Response::HTTP_OK);
            }


            $employeeId = $employee_profile->id;
            $recommending = ["for recommending approval", "for approving approval", "approved", "declined by recommending officer"];
            $approving = ["for approving approval", "approved", "declined by approving officer"];

            /**
             * Supervisor = for recommending, for approving, approved, de
             */
            $leave_applications = LeaveApplication::select('leave_applications.*')
                ->where(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('leave_applications.status', $recommending)
                        ->where('leave_applications.recommending_officer', $employeeId);
                })
                ->orWhere(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('leave_applications.status', $approving)
                        ->where('leave_applications.approving_officer', $employeeId);
                })
                ->groupBy(
                    'id',
                    'employee_profile_id',
                    'leave_type_id',
                    'date_from',
                    'date_to',
                    'country',
                    'city',
                    'is_outpatient',
                    'illness',
                    'is_masters',
                    'is_board',
                    'is_commutation',
                    'applied_credits',
                    'status',
                    'remarks',
                    'without_pay',
                    'reason',
                    'hrmo_officer',
                    'recommending_officer',
                    'approving_officer',
                    'created_at',
                    'updated_at'
                )
                ->get();


            // if (Helpers::getChiefOfficer() === $employee_profile->id) {
            //     $leave_applications = [];
            //     $divisions = Division::all();

            //     foreach ($divisions as $division) {
            //         if ($division->code === 'OMCC') {
            //             $leave_application_under_omcc = LeaveApplication::join('employee_profile as emp', 'emp.', 'employee_profile_id')
            //                 ->join('assign_areas as aa', 'aa.employee_profile_id', 'emp.id')->where('aa.division_id', $division->id)
            //                 ->where('recommending_officer', $division->chief_employee_profile_id)->get();

            //             $leave_applications = [...$leave_applications, ...$leave_application_under_omcc];
            //             continue;
            //         }
            //         $leave_application_per_division_head = LeaveApplication::where('for approving_officer')->where('approving_officer', $division->chief_employee_profile_id)->get();
            //         $leave_applications = [...$leave_applications, ...$leave_application_per_division_head];
            //     }

            //     return response()->json([
            //         'data' => LeaveApplicationResource::collection($leave_applications),
            //         'message' => 'Retrieve all leave application records.'
            //     ], Response::HTTP_OK);
            // }

            // /**
            //  * For employee that has position
            //  * Only for approving application status
            //  */

            // $position = $employee_profile->position();
            // if ($position !== null && $position['position'] !== 'Unit Head' && !str_contains($position['position'], 'OIC')) {
            //     $leave_applications = LeaveApplication::where('recommending_officer', $employee_profile->id)->get();
            //     $approving_applications = LeaveApplication::where('approving_officer', $employee_profile->id)->get();
            //     $leave_applications = [...$leave_applications, ...$approving_applications];

            //     return response()->json([
            //         'data' => LeaveApplicationResource::collection($leave_applications),
            //         'message' => 'Retrieve all leave application records.'
            //     ], Response::HTTP_OK);
            // }

            // $leave_applications = LeaveApplication::where('employee_profile_id', $employee_profile->id)->get();

            return response()->json([
                'data' => LeaveApplicationResource::collection($leave_applications),
                'message' => 'Retrieve all leave application records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approved($id, PasswordApprovalRequest $request)
    {
        try {

            
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

        
            $leave_application = LeaveApplication::find($id);

            if (!$leave_application) {
                return response()->json(["message" => "No leave application with id " . $id], Response::HTTP_NOT_FOUND);
            }

            $position = $employee_profile->position();
            $status = '';
            $log_status = '';

            switch ($leave_application->status) {
                case 'applied':
                 
                    if (Helpers::getHrmoOfficer() !== $employee_profile->id) {
                        return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
                    }
                    $status = 'for recommending approval';
                    $log_status = 'Approved by HRMO';
                    $leave_application->update(['status' => $status]);
                    break;
                case 'for recommending approval':
                  
                    if ($position === null || str_contains($position['position'], 'Unit')) {
                        return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
                    }
                    $status = 'for approving approval';
                    $log_status = 'Approved by Recommending Officer';
                    $leave_application->update(['status' => $status]);
                    break;
                case 'for approving approval':
                    $approving =  Helpers::getRecommendingAndApprovingOfficer($employee_profile->assignedArea->findDetails(),$leave_application->employee_profile_id )['approving_officer'];
                    if ($approving !== $employee_profile->id) {
                        return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
                    }
                    $status = 'approved';
                    $log_status = 'Approved by Approving Officer';
                    $leave_application->update(['status' => $status]);
                    break;
            }

            LeaveApplicationLog::create([
                'action_by' => $employee_profile->id,
                'leave_application_id' => $leave_application->id,
                'action' => $log_status
            ]);

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'message' => 'Successfully approved application.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userLeaveApplication(Request $request)
    {
        try {
            $employee_profile = $request->user;

            $leave_applications = LeaveApplication::where('employee_profile_id', $employee_profile->id)->get();

            return response()->json([
                'data' => LeaveApplicationResource::collection($leave_applications),
                'message' => 'Retrieve all leave application records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(LeaveApplicationRequest $request)

    {
        try {

            $employee_profile = $request->user;
            $recommending_and_approving = Helpers::getRecommendingAndApprovingOfficer($employee_profile->assignedArea->findDetails(), $employee_profile->id);
            $hrmo_officer = Helpers::getHrmoOfficer();

            $cleanData = [];

            $start = Carbon::parse($request->date_from);
            $end = Carbon::parse($request->date_to);

            $daysDiff = $start->diffInDays($end) + 1;

            $leave_type = LeaveType::find($request->leave_type_id);
            //   return response()->json(['message' => $leave_type->period], 401);

            if ($leave_type->is_special) {

                if ($leave_type->period < $daysDiff) {
                    return response()->json(['message' => 'Exceeds days entitled for ' . $leave_type->name], Response::HTTP_FORBIDDEN);
                }

                $cleanData['applied_credits'] = $daysDiff;
                $cleanData['employee_profile_id'] = $employee_profile->id;
                $cleanData['hrmo_officer'] = $hrmo_officer;
                $cleanData['recommending_officer'] = $recommending_and_approving['recommending_officer'];
                $cleanData['approving_officer'] = $recommending_and_approving['approving_officer'];
                $cleanData['status'] = 'applied';

                foreach ($request->all() as $key => $leave) {
                    if (is_bool($leave)) {
                        $cleanData[$key] = $leave === 0 ? false : true;
                    }
                    if (is_array($leave)) {
                        $cleanData[$key] = $leave;
                        continue;
                    }
                    if ($key === 'user' || $key === 'requirements')
                        continue;
                    if ($leave === 'null') {
                        $cleanData[$key] = $leave;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($leave);
                }

                $leave_application = LeaveApplication::create($cleanData);


                if ($request->requirements) {
                    $index = 0;
                    $requirements_name = $request->requirements_name;

                    foreach ($request->file('requirements') as $key => $file) {
                        $fileName = $file->getClientOriginalName();
                        $size = filesize($file);
                        $file_name_encrypted = Helpers::checkSaveFile($file, '/requirements');

                        LeaveApplicationRequirement::create([
                            'leave_application_id' => $leave_application->id,
                            'file_name' => $fileName,
                            'name' => $requirements_name[$index],
                            'path' => $file_name_encrypted,
                            'size' => $size,
                        ]);
                        $index++;
                    }
                }

                LeaveApplicationLog::create([
                    'action_by' => $employee_profile->id,
                    'leave_application_id' => $leave_application->id,
                    'action' => 'Applied'
                ]);
            } else {
                $employee_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)
                    ->where('leave_type_id', $request->leave_type_id)->first();



                if ($request->without_pay == 0 && $employee_credit->total_leave_credits < $daysDiff) {

                    return response()->json(['message' => 'Insufficient leave credits.'], Response::HTTP_BAD_REQUEST);
                } else {
                    $cleanData['applied_credits'] = $daysDiff;
                    $cleanData['employee_profile_id'] = $employee_profile->id;
                    $cleanData['hrmo_officer'] = $hrmo_officer;
                    $cleanData['recommending_officer'] = $recommending_and_approving['recommending_officer'];
                    $cleanData['approving_officer'] = $recommending_and_approving['approving_officer'];
                    $cleanData['status'] = 'applied';

                    foreach ($request->all() as $key => $leave) {
                        if (is_bool($leave)) {
                            $cleanData[$key] = $leave === 0 ? false : true;
                        }
                        if (is_array($leave)) {
                            $cleanData[$key] = $leave;
                            continue;
                        }
                        if ($key === 'user' || $key === 'requirements')
                            continue;
                        if ($leave === 'null') {
                            $cleanData[$key] = $leave;
                            continue;
                        }
                        $cleanData[$key] = strip_tags($leave);
                    }

                    $leave_application = LeaveApplication::create($cleanData);

                    if ($request->without_pay == 0) {
                        $previous_credit = $employee_credit->total_leave_credits;

                        $employee_credit->update(['total_leave_credits' => $employee_credit->total_leave_credits - $daysDiff]);

                        EmployeeLeaveCreditLogs::create([
                            'employee_leave_credit_id' => $employee_credit->id,
                            'previous_credit' => $previous_credit,
                            'leave_credits' => $daysDiff
                        ]);
                    }

                    if ($request->requirements) {
                        $index = 0;
                        $requirements_name = $request->requirements_name;

                        foreach ($request->file('requirements') as $key => $file) {
                            $fileName = $file->getClientOriginalName();
                            $size = filesize($file);
                            $file_name_encrypted = Helpers::checkSaveFile($file, '/requirements');

                            LeaveApplicationRequirement::create([
                                'leave_application_id' => $leave_application->id,
                                'file_name' => $fileName,
                                'name' => $requirements_name[$index],
                                'path' => $file_name_encrypted,
                                'size' => $size,
                            ]);
                            $index++;
                        }
                    }

                    LeaveApplicationLog::create([
                        'action_by' => $employee_profile->id,
                        'leave_application_id' => $leave_application->id,
                        'action' => 'Applied'
                    ]);
                }
            }



            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'message' => 'Successfully applied for ' . $leave_type->name
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $leave_application = LeaveApplication::find($id);

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'message' => 'Retrieve leave application record.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function declined($id, PasswordApprovalRequest $request)
    {
        try {
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $leave_application = LeaveApplication::find($id);
            $leave_type = $leave_application->leaveType;
            $leave_application_hrmo=$leave_application->hrmo_officer;
            $leave_application_recommending=$leave_application->recommending_officer  ;
            $leave_application_approving=$leave_application->approving_officer  ;
            
            if ($employee_profile->id === $leave_application_hrmo) {
                $status='declined by hrmo officer';
            }
            else if($employee_profile->id === $leave_application_recommending)
            {
                $status='declined by recommending officer';
            }
            else if($employee_profile->id === $leave_application_approving)
            {
                $status='declined by approving officer';
            }

            // return response()->json(['message' => $leave_type->is_special], 401);

            $leave_application->update([
                'status' => $status,
                'reason' => strip_tags($request->reason)
            ]);

            if (!$leave_type->is_special) {
                $employee_credit = EmployeeLeaveCredit::where('employee_profile_id', $leave_application->employee_profile_id)
                    ->where('leave_type_id', $leave_application->leave_type_id)->first();

                $current_leave_credit = $employee_credit->total_leave_credits;

                $employee_credit->update([
                    'total_leave_credits' => $current_leave_credit + $leave_application->leave_credits
                ]);


                EmployeeLeaveCreditLogs::create([
                    'employee_leave_credit_id' => $employee_credit->id,
                    'previous_credit' => $current_leave_credit,
                    'leave_credits' => $leave_application->applied_credits
                ]);
            }


            LeaveApplicationLog::create([
                'action_by' => $employee_profile->id,
                'leave_application_id' => $leave_application->id,
                'action' => 'Declined'
            ]);

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'message' => 'Declined leave application successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function printLeaveForm($id)
    {
        try {
            $data = LeaveApplication::with(['employeeProfile', 'leaveType', 'recommendingOfficer', 'approvingOfficer'])->where('id', $id)->first();
            $leave_type = LeaveTypeResource::collection(LeaveType::all());
            $hrmo_officer = Section::with(['supervisor'])->where('code', 'HRMO')->first();

            // return view('leave_from.leave_application_form', compact('data', 'leave_type', 'hrmo_officer'));

            $options = new Options();
            $options->set('isPhpEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->getOptions()->setChroot([base_path() . '/public/storage']);
            $html = view('leave_from.leave_application_form', compact('data', 'leave_type', 'hrmo_officer'))->render();
            $dompdf->loadHtml($html);

            $dompdf->setPaper('Legal', 'portrait');
            $dompdf->render();
            $filename = 'Leave Application('. $data->employeeProfile->personalInformation->name() .').pdf';

            /* Downloads as PDF */
            $dompdf->stream($filename);
            
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => true]);
        }
    }
}
