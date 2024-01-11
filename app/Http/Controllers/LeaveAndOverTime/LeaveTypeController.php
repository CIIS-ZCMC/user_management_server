<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use Intervention\Image\Facades\Image;
use Carbon\Carbon;
use App\Models\LeaveType;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeLeaveCredit;
use App\Http\Resources\LeaveType as ResourcesLeaveType;
use App\Models\EmployeeLeaveCredit as ModelsEmployeeLeaveCredit;
use App\Models\EmployeeProfile;
use App\Models\LeaveAttachment;
use App\Models\LeaveCredit;
use App\Models\LeaveTypeLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use \Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
class LeaveTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
        // $leaveTypes = LeaveType::with('logs.employeeProfile.personalInformation','requirements.logs.employeeProfile')->get();
        $leave_types = LeaveType::with('logs.employeeProfile.personalInformation', 'requirements.logs.employeeProfile.personalInformation','attachments')->get();
        $leave_types_result = $leave_types->map(function ($leave_type) {
            $attachmentsData = $leave_type->attachments ? $leave_type->attachments : collect();
            $requirementsData = $leave_type->requirements ? $leave_type->requirements : collect();
                return [
                    'id' => $leave_type->id,
                    'name' => $leave_type->name,
                    'description' => $leave_type->description,
                    'period' => $leave_type->period,
                    'file_date' => $leave_type->file_date,
                    'code' => $leave_type->code,
                    'is_active' => $leave_type->is_active,
                    'is_special' => $leave_type->is_special,
                    'is_country' => $leave_type->is_country,
                    'is_illness' => $leave_type->is_illness,
                    'is_days_recommended' => $leave_type->is_days_recommended,
                    'leave_credit_year' => $leave_type->leave_credit_year,
                    'date_created' => $leave_type->created_at,
                    'logs' => $leave_type->logs->map(function ($log) {
                        $process_name=$log->action;
                        $action ="";
                        $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;

                        $date=$log->date;
                        $formatted_date=Carbon::parse($date)->format('M d,Y');
                        return [
                            'id' => $log->id,
                            'leave_application_id' => $log->leave_application_id,
                            'action_by' => "{$first_name} {$last_name}" ,
                            'position' => $log->employeeProfile->assignedArea->designation->code ?? null,
                            'action' => $log->action,
                            'date' => $formatted_date,
                            'time' => $log->time,

                        ];
                    }),
                    'requirements' => $requirementsData->map(function ($requirement) {
                        return [
                            'id' => $requirement->id,
                            'name' => $requirement->name,
                            'logs' => $requirement->logs->map(function ($log) {
                                $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null ;
                                $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                return [
                                    'id' => $log->id,
                                    'action_by' => "{$first_name} {$last_name}",
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'action' => $log->action,
                                    'date' => $log->date,
                                    'time' => $log->time,
                                ];
                            }),
                        ];
                    }),
                    'attachments' => $attachmentsData->map(function ($attachment) {
                        return [
                            'id' => $attachment->id,
                            'name' => $attachment->file_name,
                            'path' => $attachment->path,
                            'size' => $attachment->size,
                        ];
                    }),
                ];
            });

        return response()->json(['data' => $leave_types_result], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }

    }

    /**
     * Show the form for creating a new resource.
     */
    public function select(Request $request)
    {
        try{
            $user=$request->user;
            // $leaveTypes = LeaveType::with('EmployeeLeaveCredits')
            // ->whereHas('EmployeeLeaveCredits', function ($query) use ($user) {
            //         $query->where('employee_profile_id', $user->id);
            //     })
            // ->get()
            // ->map(function ($leaveType) {
            //     $balance = $leaveType->EmployeeLeaveCredits->isEmpty()
            //     ? 0
            //     : $leaveType->EmployeeLeaveCredits->sum(function ($credit) {
            //         $operationMultiplier = ($credit->operation === 'add') ? 1 : -1;
            //         return $operationMultiplier * (float) $credit->credit_value;
            //     });

            //     return [
            //                     'value' => "$leaveType->id",
            //                     'label' => $leaveType->name,
            //                     'balance' => $balance,
            //                     'description' => $leaveType->description,
            //                     'file_date' => $leaveType->file_date,
            //                     'period' => $leaveType->period,
            //                     'is_country' => $leaveType->is_country,
            //                     'is_illness' => $leaveType->is_illness,
            //                     'is_days_recommended' => $leaveType->is_days_recommended,
            //                     'requirements' => $leaveType->requirements->map(function ($requirement) {
            //                         return [
            //                             'id' => $requirement->id,
            //                             'name' => $requirement->name,
            //                         ];
            //                     }),
            //                 ];

            // });

            $leaveTypes = LeaveType::leftJoin('employee_leave_credits', function ($join) use ($user) {
                $join->on('leave_types.id', '=', 'employee_leave_credits.leave_type_id')
                    ->where('employee_leave_credits.employee_profile_id', '=', $user->id);
            })
            ->select(
                'leave_types.id',
                'leave_types.name',
                'leave_types.description',
                'leave_types.file_date',
                'leave_types.period',
                'leave_types.is_country',
                'leave_types.is_illness',
                'leave_types.is_days_recommended',
                DB::raw('IFNULL(SUM(CASE WHEN employee_leave_credits.operation = "add" THEN employee_leave_credits.credit_value ELSE -employee_leave_credits.credit_value END), 0) as balance')
            )
            ->groupBy(
                'leave_types.id',
                'leave_types.name',
                'leave_types.description',
                'leave_types.file_date',
                'leave_types.period',
                'leave_types.is_country',
                'leave_types.is_illness',
                'leave_types.is_days_recommended'
            )
            ->get();

        // Map the result
        $result = $leaveTypes->map(function ($leaveType) {
            return [
                'value' => $leaveType->id,
                'label' => $leaveType->name,
                'balance' => $leaveType->balance,
                'description' => $leaveType->description,
                'file_date' => $leaveType->file_date,
                'period' => $leaveType->period,
                'is_country' => $leaveType->is_country,
                'is_illness' => $leaveType->is_illness,
                'is_days_recommended' => $leaveType->is_days_recommended,
            ];
        });

                 return response()->json(['data' => $result], Response::HTTP_OK);
            }catch(\Throwable $th){

                return response()->json(['message' => $th->getMessage()], 500);
            }
    }

    public function store(Request $request)
    {

        try{
            $user=$request->user;
            $validatedData = $request->validate([
                'name' => 'required|string',
                'attachments.*' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);
            $employee_id = $request->employee_id;
            $filename="";
            $process_name="Add";
            $leave_type = new LeaveType();
            $leave_type->name = ucwords($request->name);
            $leave_type->description = $request->description;
            $leave_type->period = $request->period;
            $leave_type->file_date = $request->file_date;
            $input_name = $request->name;
            $name_codes = explode(' ', $input_name);
            $firstLetters = '';
            foreach ($name_codes as $name_code) {
                $firstLetters .= strtoupper(substr($name_code, 0, 1));
            }
            $leave_type->code = $firstLetters;
            $leave_type->is_active = true;
            $leave_type->is_special = $request->input('is_special');
            $leave_type->is_country = $request->input('is_country');
            $leave_type->is_illness = $request->input('is_illness');
            $leave_type->is_days_recommended = $request->input('is_days_recommended');
            if (!empty($request->leave_credit_year))
            {
                $leave_type->leave_credit_year = $request->leave_credit_year;
            }
            else
            {
                $leave_type->leave_credit_year = "";
            }
            $leave_type->save();
            $attachment=$request->file('attachments');
            $leave_type_id=$leave_type->id;
            if($request->hasFile('attachments'))
            {
                foreach ($request->file('attachments') as $file) {
                    $folderName = 'attachments';
                    $fileName = $file->getClientOriginalName();
                    // $fileName=pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension  = $file->getClientOriginalName();
                    $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
                    Storage::makeDirectory('public/' . $folderName);
                    $file->storeAs('public/' . $folderName, $uniqueFileName);
                    $size = $file->getSize();
                    $path = $folderName .'/'. $uniqueFileName;
                    $leave_attachment= new LeaveAttachment();
                    $leave_attachment->file_name= $fileName;
                    $leave_attachment->leave_type_id = $leave_type_id;
                    $leave_attachment->path = $path;
                    $leave_attachment->size = $size;
                    $leave_attachment->save();
                }

            }
            $selectedRequirements = $request->input('requirements');
            $leave_type->requirements()->sync($selectedRequirements);
            $columnsString="";
            $this->storeLeaveTypeLog($leave_type_id,$process_name,$columnsString,$user->id);
            $leave_types = LeaveType::with('logs.employeeProfile.personalInformation', 'requirements.logs.employeeProfile.personalInformation','attachments')
            ->where('id',$leave_type_id)->get();
                $leave_types_result = $leave_types->map(function ($leave_type) {
                $attachmentsData = $leave_type->attachments ? $leave_type->attachments : collect();
                $requirementsData = $leave_type->requirements ? $leave_type->requirements : collect();
                    return [
                        'id' => $leave_type->id,
                        'name' => $leave_type->name,
                        'description' => $leave_type->description,
                        'period' => $leave_type->period,
                        'file_date' => $leave_type->file_date,
                        'code' => $leave_type->code,
                        'is_active' => $leave_type->is_active,
                        'is_special' => $leave_type->is_special,
                        'is_country' => $leave_type->is_country,
                        'is_illness' => $leave_type->is_illness,
                        'is_days_recommended' => $leave_type->is_days_recommended,
                        'leave_credit_year' => $leave_type->leave_credit_year,
                        'date_created' => $leave_type->created_at,
                        'logs' => $leave_type->logs->map(function ($log) {
                            $process_name=$log->action;
                            $action ="";
                            $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;

                            $date=$log->date;
                            $formatted_date=Carbon::parse($date)->format('M d,Y');
                            return [
                                'id' => $log->id,
                                'leave_application_id' => $log->leave_application_id,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,

                            ];
                        }),
                        'requirements' => $requirementsData->map(function ($requirement) {
                            return [
                                'id' => $requirement->id,
                                'name' => $requirement->name,
                                'logs' => $requirement->logs->map(function ($log) {
                                    $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null ;
                                    $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                    return [
                                        'id' => $log->id,
                                        'action_by' => "{$first_name} {$last_name}",
                                        'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                        'action' => $log->action,
                                        'date' => $log->date,
                                        'time' => $log->time,
                                    ];
                                }),
                            ];
                        }),
                        'attachments' => $attachmentsData->map(function ($attachment) {
                            return [
                                'id' => $attachment->id,
                                'name' => $attachment->file_name,
                                'path' => $attachment->path,
                                'size' => $attachment->size,
                            ];
                        }),
                    ];
                });
            $singleArray = array_merge(...$leave_types_result);
            return response()->json(['message' => 'Leave Type has been sucessfully saved','data' => $singleArray ], Response::HTTP_OK);

        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id,LeaveType $leaveType)
    {

        try{
            $data = LeaveCredit::find($id);

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){

            return response() -> json(['message' => $th -> getMessage()], 500);
        }

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveType $leaveType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id,Request $request, LeaveType $leaveType)
    {
        try{
            $user=$request->user;
            $validatedData = $request->validate([
                'name' => 'required|string',
                'attachments.*' => 'required|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);
            $leave_type = LeaveType::findOrFail($id);
            $originalValues = $leave_type->getOriginal();
            $columnsString="";
            $leave_type->name = ucwords($request->name);
            $leave_type->description = $request->description;
            $leave_type->period = $request->period;
            $leave_type->file_date = $request->file_date;
            $input_name = $request->name;
            $name_codes = explode(' ', $input_name);
            $firstLetters = '';
            foreach ($name_codes as $name_code) {
                $firstLetters .= strtoupper(substr($name_code, 0, 1));
            }
            $leave_type->code = $firstLetters;
            // $leave_type->is_special = $request->input('is_special');
            $leave_type->is_country = $request->input('is_country');
            $leave_type->is_illness = $request->input('is_illness');
            $leave_type->is_days_recommended = $request->input('is_days_recommended ');

            if (!empty($request->leave_credit_year))
            {
                $leave_type->leave_credit_year = $request->leave_credit_year;
            }
            else
            {
                $leave_type->leave_credit_year = "";
            }
            $leave_type->update();
            if ($leave_type->isDirty()) {
                $changedColumns = $leave_type->getChanges();

                $columnsString = implode(', ', $changedColumns);

            }
            $process_name="Update";
            $leave_type_id=$leave_type->id;
                if ($request->hasFile('attachments')) {
                    $leaveType = LeaveType::with('attachments')->findOrFail($id);
                    foreach ($leaveType->attachments as $attachment) {
                        $filePath = $attachment->path;
                        Storage::delete($filePath);
                        $attachment->delete();
                    }
                    foreach ($request->file('attachments') as $file) {
                        $folderName = 'attachments';
                        $fileName=pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $extension  = $file->getClientOriginalName();
                        $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
                        Storage::makeDirectory('public/' . $folderName);
                        $file->storeAs('public/' . $folderName, $uniqueFileName);
                        $size = $file->getSize();
                        $path = $folderName .'/'. $uniqueFileName;
                        $leave_attachment= new LeaveAttachment();
                        $leave_attachment->file_name= $fileName;
                        $leave_attachment->leave_type_id = $leave_type_id;
                        $leave_attachment->path = $path;
                        $leave_attachment->size = $size;
                        $leave_attachment->save();
                    }

            }
            $selectedRequirements = $request->input('requirements', []);
            $leave_type->requirements()->sync($selectedRequirements);
            $columnsString="";
            $this->storeLeaveTypeLog($leave_type_id,$process_name,$columnsString,$user->id);
            $leave_types = LeaveType::with('logs.employeeProfile.personalInformation', 'requirements.logs.employeeProfile.personalInformation','attachments')
            ->where('id',$leave_type_id)->get();
                $leave_types_result = $leave_types->map(function ($leave_type) {
                $attachmentsData = $leave_type->attachments ? $leave_type->attachments : collect();
                $requirementsData = $leave_type->requirements ? $leave_type->requirements : collect();
                    return [
                        'id' => $leave_type->id,
                        'name' => $leave_type->name,
                        'description' => $leave_type->description,
                        'period' => $leave_type->period,
                        'file_date' => $leave_type->file_date,
                        'code' => $leave_type->code,
                        'is_active' => $leave_type->is_active,
                        'is_special' => $leave_type->is_special,
                        'is_country' => $leave_type->is_country,
                        'is_illness' => $leave_type->is_illness,
                        'is_days_recommended' => $leave_type->is_days_recommended,
                        'leave_credit_year' => $leave_type->leave_credit_year,
                        'date_created' => $leave_type->created_at,
                        'logs' => $leave_type->logs->map(function ($log) {
                            $process_name=$log->action;
                            $action ="";
                            $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;

                            $date=$log->date;
                            $formatted_date=Carbon::parse($date)->format('M d,Y');
                            return [
                                'id' => $log->id,
                                'leave_application_id' => $log->leave_application_id,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,

                            ];
                        }),
                        'requirements' => $requirementsData->map(function ($requirement) {
                            return [
                                'id' => $requirement->id,
                                'name' => $requirement->name,
                                'logs' => $requirement->logs->map(function ($log) {
                                    $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null ;
                                    $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                    return [
                                        'id' => $log->id,
                                        'action_by' => "{$first_name} {$last_name}",
                                        'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                        'action' => $log->action,
                                        'date' => $log->date,
                                        'time' => $log->time,
                                    ];
                                }),
                            ];
                        }),
                        'attachments' => $attachmentsData->map(function ($attachment) {
                            return [
                                'id' => $attachment->id,
                                'name' => $attachment->file_name,
                                'path' => $attachment->path,
                                'size' => $attachment->size,
                            ];
                        }),
                    ];
                });
                $singleArray = array_merge(...$leave_types_result);
            return response()->json(['message' => 'Leave Type has been sucessfully updated','data' => $singleArray ], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveType $leaveType)
    {
        //
    }

    public function storeLeaveTypeLog($leave_type_id,$process_name,$changedfields,$user_id)
    {

        try {

            $data = [
                'leave_type_id' => $leave_type_id,
                'action_by_id' => $user_id,
                'action' => $process_name,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'fields' => $changedfields
            ];

            $leave_type_log = LeaveTypeLog::create($data);
            return $leave_type_log;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }


    public function deactivateLeaveType(Request $request,$leave_type_id)
    {
        try{
            $columnsString="";
            $user = $request->user;
            $password_decrypted = Crypt::decryptString($user['password_encrypted']);
            $password = strip_tags($request->password);
                if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                    return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
                }
                else
                {
                    $deactivate_leave_type = LeaveType::findOrFail($leave_type_id);
                    $deactivate_leave_type->is_active=false;
                    $deactivate_leave_type->update();
                    $process_name="Deactivate";
                    $this->storeLeaveTypeLog($leave_type_id,$process_name,$columnsString,$user->id);
                    $leave_types = LeaveType::with('logs.employeeProfile.personalInformation', 'requirements.logs.employeeProfile.personalInformation','attachments')
                    ->where('id',$leave_type_id)->get();
                        $leave_types_result = $leave_types->map(function ($leave_type) {
                        $attachmentsData = $leave_type->attachments ? $leave_type->attachments : collect();
                        $requirementsData = $leave_type->requirements ? $leave_type->requirements : collect();
                            return [
                                'id' => $leave_type->id,
                                'name' => $leave_type->name,
                                'description' => $leave_type->description,
                                'period' => $leave_type->period,
                                'file_date' => $leave_type->file_date,
                                'code' => $leave_type->code,
                                'is_active' => $leave_type->is_active,
                                'is_special' => $leave_type->is_special,
                                'is_country' => $leave_type->is_country,
                                'is_illness' => $leave_type->is_illness,
                                'is_days_recommended' => $leave_type->is_days_recommended,
                                'leave_credit_year' => $leave_type->leave_credit_year,
                                'date_created' => $leave_type->created_at,
                                'logs' => $leave_type->logs->map(function ($log) {
                                    $process_name=$log->action;
                                    $action ="";
                                    $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                                    $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;

                                    $date=$log->date;
                                    $formatted_date=Carbon::parse($date)->format('M d,Y');
                                    return [
                                        'id' => $log->id,
                                        'leave_application_id' => $log->leave_application_id,
                                        'action_by' => "{$first_name} {$last_name}" ,
                                        'position' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                        'action' => $log->action,
                                        'date' => $formatted_date,
                                        'time' => $log->time,

                                    ];
                                }),
                                'requirements' => $requirementsData->map(function ($requirement) {
                                    return [
                                        'id' => $requirement->id,
                                        'name' => $requirement->name,
                                        'logs' => $requirement->logs->map(function ($log) {
                                            $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null ;
                                            $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                            return [
                                                'id' => $log->id,
                                                'action_by' => "{$first_name} {$last_name}",
                                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                'action' => $log->action,
                                                'date' => $log->date,
                                                'time' => $log->time,
                                            ];
                                        }),
                                    ];
                                }),
                                'attachments' => $attachmentsData->map(function ($attachment) {
                                    return [
                                        'id' => $attachment->id,
                                        'name' => $attachment->file_name,
                                        'path' => $attachment->path,
                                        'size' => $attachment->size,
                                    ];
                                }),
                            ];
                        });
                    return response()->json(['message' => 'Leave Type has been sucessfully deactivated','data' => $leave_types_result ], Response::HTTP_OK);

                }


        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }

    }

    public function reactivateLeaveType(Request $request,$leave_type_id)
    {
        try{
            $columnsString="";
            $user = $request->user;
            $password=$request->password;
            $password_decrypted = Crypt::decryptString($user['password_encrypted']);
            $password = strip_tags($request->password);
            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                    return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }
            else
            {
                $reactivate_leave_type = LeaveType::findOrFail($leave_type_id);
                $reactivate_leave_type->is_active=true;
                $reactivate_leave_type->update();
                $process_name="Reactivate";
                 $this->storeLeaveTypeLog($leave_type_id,$process_name,$columnsString,$user->id);
                 $leave_types = LeaveType::with('logs.employeeProfile.personalInformation', 'requirements.logs.employeeProfile.personalInformation','attachments')
                 ->where('id',$leave_type_id)->get();
                     $leave_types_result = $leave_types->map(function ($leave_type) {
                     $attachmentsData = $leave_type->attachments ? $leave_type->attachments : collect();
                     $requirementsData = $leave_type->requirements ? $leave_type->requirements : collect();
                         return [
                             'id' => $leave_type->id,
                             'name' => $leave_type->name,
                             'description' => $leave_type->description,
                             'period' => $leave_type->period,
                             'file_date' => $leave_type->file_date,
                             'code' => $leave_type->code,
                             'is_active' => $leave_type->is_active,
                             'is_special' => $leave_type->is_special,
                             'is_country' => $leave_type->is_country,
                             'is_illness' => $leave_type->is_illness,
                             'is_days_recommended' => $leave_type->is_days_recommended,
                             'leave_credit_year' => $leave_type->leave_credit_year,
                             'date_created' => $leave_type->created_at,
                             'logs' => $leave_type->logs->map(function ($log) {
                                 $process_name=$log->action;
                                 $action ="";
                                 $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                                 $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;

                                 $date=$log->date;
                                 $formatted_date=Carbon::parse($date)->format('M d,Y');
                                 return [
                                     'id' => $log->id,
                                     'leave_application_id' => $log->leave_application_id,
                                     'action_by' => "{$first_name} {$last_name}" ,
                                     'position' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                     'action' => $log->action,
                                     'date' => $formatted_date,
                                     'time' => $log->time,

                                 ];
                             }),
                             'requirements' => $requirementsData->map(function ($requirement) {
                                 return [
                                     'id' => $requirement->id,
                                     'name' => $requirement->name,
                                     'logs' => $requirement->logs->map(function ($log) {
                                         $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null ;
                                         $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                         return [
                                             'id' => $log->id,
                                             'action_by' => "{$first_name} {$last_name}",
                                             'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                             'action' => $log->action,
                                             'date' => $log->date,
                                             'time' => $log->time,
                                         ];
                                     }),
                                 ];
                             }),
                             'attachments' => $attachmentsData->map(function ($attachment) {
                                 return [
                                     'id' => $attachment->id,
                                     'name' => $attachment->file_name,
                                     'path' => $attachment->path,
                                     'size' => $attachment->size,
                                 ];
                             }),
                         ];
                     });
                 return response()->json(['message' => 'Leave Type has been sucessfully reactivated','data' => $leave_types_result ], Response::HTTP_OK);

            }


        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }

    }

}
