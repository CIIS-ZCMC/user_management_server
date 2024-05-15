<?php

namespace App\Http\Controllers\Schedule;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\TimeAdjustmentRequest;
use App\Http\Resources\TimeAdjustmentResource;
use App\Models\DailyTimeRecords;
use App\Models\EmployeeProfile;
use App\Models\Section;
use App\Models\TimeAdjustment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;


class TimeAdjustmentController extends Controller
{
    private $CONTROLLER_NAME = 'Time Adjustment';
    private $PLURAL_MODULE_NAME = 'time adjustments';
    private $SINGULAR_MODULE_NAME = 'time adjustment';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            Helpers::registerSystemLogs($request, null, true, 'Success in fetching ' . $this->PLURAL_MODULE_NAME . '.');
            return response()->json(['data' => TimeAdjustmentResource::collection(TimeAdjustment::all())], Response::HTTP_OK);

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
            $data = TimeAdjustment::where('employee_profile_id', $user->id)->get();
            return response()->json(['data' => TimeAdjustmentResource::collection($data)], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

                if ($key === 'attachment') {
                    $attachment = Helpers::checkSaveFile($request->attachment, '/time_adjustment');
                    if (is_string($attachment)) {
                        $cleanData['attachment'] = $request->attachment === null || $request->attachment === 'null' ? null : $attachment;
                    }
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $user = $request->user;

            $recommending_officer = null;
            $approving_officer = Section::where('code', 'HRMO')->first()->supervisor_employee_profile_id;

            $employee = EmployeeProfile::find($cleanData['employee_profile_id']);
            $employee_area = $employee->assignedArea->findDetails();

            if ($employee->biometric_id === null) {
                return response()->json(['message' => 'Biometric ID is not yet registered'], Response::HTTP_NOT_FOUND);
            }

            $dtr = DailyTimeRecords::where([
                ['biometric_id', '=', $employee->biometric_id],
                ['dtr_date', '=', Carbon::parse($cleanData['date'])->format('Y-m-d')],
            ])->first();

            if ($dtr === null) {
                $dtr = DailyTimeRecords::create([
                    'biometric_id' => $employee->biometric_id,
                    'dtr_date' => $cleanData['date'],
                    'first_in' => $cleanData['first_in'],
                    'first_out' => $cleanData['first_out'],
                    'second_in' => $cleanData['second_in'],
                    'second_out' => $cleanData['second_out'],
                ]);
            }

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
                    $recommending_officer = $employee->assignedArea->section->supervisor_employee_profile_id;
                    break;
            }

            if ($approving_officer === null) {
                return response()->json(['message' => 'No approving officer assigned.'], Response::HTTP_FORBIDDEN);
            }

            $cleanData['daily_time_record_id'] = $dtr->id;
            $cleanData['employee_profile_id'] = $employee->id;
            // $cleanData['recommending_officer'] = $recommending_officer ? $recommending_officer->id : null;
            $cleanData['approving_officer'] = $approving_officer;

            $data = TimeAdjustment::create($cleanData);

            Helpers::registerSystemLogs($request, $data['id'], true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new TimeAdjustmentResource($data),
                'logs' => Helpers::registerTimeAdjustmentLogs($data->id, $user->id, 'Store'),
                'message' => 'Request is now on-process '
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id, Request $request)
    {
        try {
            $data = TimeAdjustment::find($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $status = $request->status === 'approved' ? 'approved' : 'declined';
            $dtr = null;

            if ($request->status === 'approved' && $data->status === 'applied') {
                $dtr = DailyTimeRecords::where('dtr_date', Carbon::parse($data->date))->first();

                if ($dtr === null) {
                    $employees = EmployeeProfile::find($data->employee_profile_id);

                    $dtr = DailyTimeRecords::create([
                        'biometric_id' => $employees->biometric_id,
                        'dtr_date' => $data->date,
                        'first_in' => $data->first_in,
                        'first_out' => $data->first_out,
                        'second_in' => $data->second_in,
                        'second_out' => $data->second_out,
                    ]);
                } else {
                    $dtr->update([
                        'first_in' => $data->first_in ?? $dtr->first_in,
                        'first_out' => $data->first_out ?? $dtr->first_out,
                        'second_in' => $data->second_in ?? $dtr->second_in,
                        'second_out' => $data->second_out ?? $dtr->second_out,
                    ]);
                }
            }

            $data->update([
                'daily_time_record_id' => $dtr ? $dtr->id : ($data->daily_time_record_id ?? null),
                'approval_date' => Carbon::now(),
                'remarks' => $request->remarks,
                'status' => $status,
            ]);

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in updating.' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new TimeAdjustmentResource($data),
                'logs' => Helpers::registerTimeAdjustmentLogs($data->id, $user->id, $status),
                'message' => 'Request ' . $status
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $data = TimeAdjustment::withTrashed()->findOrFail($id);
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
