<?php

namespace App\Http\Controllers\Schedule;


use App\Models\EmployeeProfile;
use App\Models\Section;
use App\Models\TimeAdjusment;
use App\Models\DailyTimeRecords;

use App\Http\Resources\TimeAdjustmentResource;
use App\Http\Requests\TimeAdjustmentRequest;
use App\Helpers\Helpers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use DateTime;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TimeAdjusmentController extends Controller
{
    private $CONTROLLER_NAME = 'Time Shift';
    private $PLURAL_MODULE_NAME = 'time shifts';
    private $SINGULAR_MODULE_NAME = 'time shift';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            Helpers::registerSystemLogs($request, null, true, 'Success in fetching ' . $this->PLURAL_MODULE_NAME . '.');
            return response()->json(['data' => TimeAdjustmentResource::collection(TimeAdjusment::all())], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TimeAdjustmentRequest $request)
    {
        try {
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

            $user = $request->user;
            $data = null;
            $recommending_officer = null;
            $approving_officer = Section::where('code', 'HRMO')->first()->supervisor_employee_profile_id;

            $dates = $cleanData['dtr_date'];
            foreach ($dates as $key => $value) {
                $daily_time_record = DailyTimeRecords::where([
                    ['biometric_id', '=', $cleanData['biometric_id']],
                    ['dtr_date', '=', $value['date']],
                ])->first();

                if (!$daily_time_record) {
                    return response()->json(['message' => 'No DTR record found.'], Response::HTTP_NOT_FOUND);
                }

                if ($daily_time_record) {
                    $employee = EmployeeProfile::find($cleanData['employee_profile_id'])->first();
                    if ($employee) {
                        $employee_area = $employee->assignedArea->findDetails();

                        switch ($employee_area['sector']) {
                            case 'Division':
                                $recommending_officer = $employee->assignedArea->division->divisionHead;
                                break;

                            case 'Department':
                                $recommending_officer = $employee->assignedArea->department->head;
                                break;

                            case 'Section':
                                $recommending_officer = $employee->assignedArea->section->supervisor_employee_profile_id;
                                break;

                            case 'Unit':
                                $recommending_officer = $employee->assignedArea->department->head;
                                break;

                            default:
                                return response()->json(['message' => 'User has no sector'], Response::HTTP_NOT_FOUND);
                        }
                    }

                    $data = TimeAdjusment::create([
                        'first_in' => $value['firstIn'] ?? null,
                        'first_out' => $value['firstOut'] ?? null,
                        'second_in' => $value['secondIn'] ?? null,
                        'second_out' => $value['secondOut'] ?? null,
                        'employee_profile_id' => $employee->id,
                        'daily_time_record_id' => $daily_time_record->id,
                        'recommended_by' => $user->id,
                        'approve_by' => $approving_officer,
                    ]);
                }
            }

            Helpers::registerSystemLogs($request, $data['id'], true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json(['data' => $data], Response::HTTP_OK);

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
            $data = new TimeAdjustmentResource(TimeAdjusment::findOrFail($id));

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            Helpers::registerSystemLogs($request, $id, true, 'Success in fetching ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimeAdjusment $timeAdjusment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id, Request $request)
    {
        try {
            $data = TimeAdjusment::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $status = null;
            if ($request->status === 'approved') {
                switch ($data->status) {
                    case 'applied':
                        $status = 'approved';
                        break;

                    case 'declined':
                        $status = 'declined';

                    default:
                        $status = 'approved';
                        break;
                }
            } else if ($request->approval_status === 'declined') {
                $status = 'declined';
            }

            $data->update(['status' => $status, 'remarks' => $request->remarks, 'approval_date' => Carbon::now()]);

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in updating.' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => TimeAdjustmentResource::collection(TimeAdjusment::where('id', $data->id)->get()),
                // 'logs' => Helpers::registerExchangeDutyLogs($data->id, $employee_profile->id, $status),
                'msg' => 'Request '.$status
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $data = TimeAdjusment::withTrashed()->findOrFail($id);
            $data->section()->detach($data->id);

            if ($data->deleted_at != null) {
                $data->forceDelete();
            } else {
                $data->delete();
            }

            Helpers::registerSystemLogs($request, $id, true, 'Success in delete ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);

        }
    }
}
