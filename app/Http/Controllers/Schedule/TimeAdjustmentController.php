<?php

namespace App\Http\Controllers\Schedule;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DTR\DTRcontroller;
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

            // $recommending_officer = null;
            $approving_officer = Section::where('code', 'HRMO')->first()->supervisor_employee_profile_id;

            $employee = EmployeeProfile::find($cleanData['employee_profile_id']);

            if ($employee->biometric_id === null) {
                return response()->json(['message' => 'Biometric ID is not yet registered'], Response::HTTP_NOT_FOUND);
            }

            $find = TimeAdjustment::where('date', $cleanData['date'])
                ->where('employee_profile_id', $cleanData['employee_profile_id'])
                ->whereNot('status', 'declined')
                ->first();

            if ($find !== null) {
                return response()->json(['message' => 'You already have a request on date: ' . $cleanData['date']], Response::HTTP_FORBIDDEN);
            }

            // $employee_area = $employee->assignedArea->findDetails();
            // switch ($employee_area['sector']) {
            //     case 'Division':
            //         $recommending_officer = $employee->assignedArea->division->divisionHead;
            //         break;

            //     case 'Department':
            //         $recommending_officer = $employee->assignedArea->department->head;
            //         break;

            //     case 'Section':
            //         $recommending_officer = $employee->assignedArea->section->supervisor_employee_profile_id;
            //         break;

            //     case 'Unit':
            //         $recommending_officer = $employee->assignedArea->section->supervisor_employee_profile_id;
            //         break;
            // }

            if ($approving_officer === null) {
                return response()->json(['message' => 'No approving officer assigned.'], Response::HTTP_FORBIDDEN);
            }

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
                $employee = EmployeeProfile::find($data->employee_profile_id);

                $dtr = DailyTimeRecords::where([
                    ['biometric_id', '=', $employee->biometric_id],
                    ['dtr_date', '=', Carbon::parse($data->date)->format('Y-m-d')],
                ])->first();

                if ($dtr === null) {
                    $dtr = DailyTimeRecords::create([
                        'biometric_id' => $employee->biometric_id,
                        'dtr_date' => $data->date,
                        'first_in' => $data->date . " " . $data->first_in,
                        'first_out' => $data->date . " " . $data->first_out,
                        'second_in' => $data->date . " " . $data->second_in,
                        'second_out' => $data->date . " " . $data->second_out,
                        'is_time_adjustment' => 1
                    ]);
                } else {
                    $dtr->update([
                        'first_in' => $data->date . " " . $data->first_in,
                        'first_out' => $data->date . " " . $data->first_out,
                        'second_in' => $data->date . " " . $data->second_in,
                        'second_out' => $data->date . " " . $data->second_out,
                        'is_time_adjustment' => 1
                    ]);
                }
            }

            $data->update([
                'daily_time_record_id' => $dtr ? $dtr->id : ($data->daily_time_record_id ?? null),
                'approval_date' => Carbon::now(),
                'reason' => $request->reasons,
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

    public function employees(Request $request)
    {
        try {
            // Fetch all employees from the database
            $fetch_employees = EmployeeProfile::join('personal_informations', 'employee_profiles.personal_information_id', '=', 'personal_informations.id')
                ->select('employee_profiles.*') // Select fields from the EmployeeProfile table
                ->orderBy('personal_informations.last_name')
                ->get();

            // Process fetched employees
            $data = [];
            foreach ($fetch_employees as $employee) {
                $data[] = [
                    'id' => $employee->id,
                    'name' => $employee->lastNameTofirstName(),
                ];
            }

            return response()->json(['data' => $data], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeeList', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //Store new TA by clicking request time adjustment used by HRMO 
    public function request(Request $request)
    {
        try {

            $cleanData = [];
            $createdAdjustments = []; // Array to store all created adjustments

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

                if ($key === 'attachment') {
                    $attachment = Helpers::checkSaveFile($request->attachment, '/time_adjustment');
                    if (is_string($attachment)) {
                        $cleanData['attachment'] = $request->attachment === null || $request->attachment === 'null' ? null : $attachment;
                    }
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            // Continue with your existing logic
            $user = $request->user;
            $approving_officer = Section::where('code', 'HOPPS-HRMO')->first()->supervisor_employee_profile_id;

            $employee = EmployeeProfile::find($cleanData['employee_profile_id']);

            if ($employee->biometric_id === null) {
                return response()->json(['message' => 'Biometric ID is not yet registered'], Response::HTTP_NOT_FOUND);
            }

            if ($approving_officer === null) {
                return response()->json(['message' => 'No approving officer assigned.'], Response::HTTP_FORBIDDEN);
            }

            // Decode the time_records JSON string into an array
            if (isset($request->time_records)) {
                $cleanData['time_records'] = json_decode($request->time_records, true);
            } else {
                return response()->json(['message' => 'Time records are missing'], Response::HTTP_BAD_REQUEST);
            }

            foreach ($cleanData['time_records'] as $record) {
                if (empty($record['time_in']) && empty($record['break_out'])) {
                    continue;
                }

                // Check if a request already exists for this date
                // $find = TimeAdjustment::where('date', $record['date'])
                //     ->where('employee_profile_id', $cleanData['employee_profile_id'])
                //     ->whereNot('status', 'declined')
                //     ->first();

                // if ($find !== null) {
                //     return response()->json(['message' => 'You already have a request on date: ' . $record['date']], Response::HTTP_FORBIDDEN);
                // }


                // Prepare data for this specific date
                $adjustmentData = $cleanData;
                $adjustmentData['employee_profile_id'] = $employee->id;
                $adjustmentData['approving_officer'] = $approving_officer;
                $adjustmentData['date'] = $record['date'];
                $adjustmentData['status'] = $cleanData['status'];

                if ($employee->shifting === 1) {
                    $adjustmentData['first_in'] = isset($record['time_in']) ? Carbon::parse($record['time_in'])->format('H:i:s') : null;
                    $adjustmentData['first_out'] = isset($record['time_out']) ? Carbon::parse($record['time_out'])->format('H:i:s') : null;
                } else {
                    $adjustmentData['first_in'] = isset($record['time_in']) ? Carbon::parse($record['time_in'])->format('H:i:s') : null;
                    $adjustmentData['first_out'] = isset($record['break_out']) ? Carbon::parse($record['break_out'])->format('H:i:s') : null;
                    $adjustmentData['second_in'] = isset($record['break_in']) ? Carbon::parse($record['break_in'])->format('H:i:s') : null;
                    $adjustmentData['second_out'] = isset($record['time_out']) ? Carbon::parse($record['time_out'])->format('H:i:s') : null;
                }

                // Store the time adjustment for this date
                $data = TimeAdjustment::create($adjustmentData);

                $dtr = DailyTimeRecords::where([
                    ['biometric_id', '=', $employee->biometric_id],
                    ['dtr_date', '=', $adjustmentData['date']],
                ])->first();

                $firstInTime = Carbon::parse($data->date . " " . $data->first_in);
                $firstOutTime = Carbon::parse($data->date . " " . $data->first_out);
                $secondInTime = Carbon::parse($data->date . " " . $data->second_in);
                $secondOutTime = Carbon::parse($data->date . " " . $data->second_out);

                // Check if `first_in` is greater than `first_out`, indicating a date span
                if ($firstInTime->gt($firstOutTime)) {
                    $firstOutTime->addDay();
                }

                if ($dtr === null) {
                    // Create new DTR
                    $dtr = DailyTimeRecords::create([
                        'biometric_id' => $employee->biometric_id,
                        'dtr_date' => $data->date,
                        'first_in' => $data->first_in === null ? null : $firstInTime->toDateTimeString(),
                        'first_out' => $data->first_out === null ? null : $firstOutTime->toDateTimeString(),
                        'second_in' => $data->second_in === null ? null : $secondInTime->toDateTimeString(),
                        'second_out' => $data->second_out === null ? null : $secondOutTime->toDateTimeString(),
                        'is_time_adjustment' => 1
                    ]);

                    // DTR_HELPER;

                } else {
                    // Update existing DTR
                    $dtr->update([
                        'first_in' => $data->first_in === null ? null : $firstInTime->toDateTimeString(),
                        'first_out' => $data->first_out === null ? null : $firstOutTime->toDateTimeString(),
                        'second_in' => $data->second_in === null ? null : $secondInTime->toDateTimeString(),
                        'second_out' => $data->second_out === null ? null : $secondOutTime->toDateTimeString(),
                        'is_time_adjustment' => true
                    ]);
                }

                $month = Carbon::parse($dtr->dtr_date)->month;
                $year = Carbon::parse($dtr->dtr_date)->year;

                $dtrHelper = new DTRcontroller();
                $dtrHelper->RecomputeHours($dtr->biometric_id, $month, $year, $dtr->dtr_date);

                $dtr->is_time_adjustment = true;
                $dtr->update();

                $data->daily_time_record_id = $dtr->id;
                $data->update();

                // Add the created adjustment to the array
                $createdAdjustments[] = $data;

                // Log system actions
                Helpers::registerSystemLogs($request, $data['id'], true, 'Success in creating time adjustment for ' . $record['date'] . '.');
                Helpers::registerTimeAdjustmentLogs($data->id, $user->id, 'request');
            }

            return response()->json([
                'data' => TimeAdjustmentResource::collection(TimeAdjustment::all()),
                'message' => 'Time adjustments successfully created for the date range.'
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'request', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateRequest(Request $request, $id)
    {

        try {

            $data = TimeAdjustment::find($id);

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

                if ($key === 'attachment') {
                    $attachment = Helpers::checkSaveFile($request->attachment, '/time_adjustment');
                    if (is_string($attachment)) {
                        $cleanData['attachment'] = $request->attachment === null || $request->attachment === 'null' ? null : $attachment;
                    }
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $employee = EmployeeProfile::find($data->employee_profile_id);

            if ($employee->biometric_id === null) {
                return response()->json(['message' => 'Biometric ID is not yet registered'], Response::HTTP_NOT_FOUND);
            }


            $daily_time_record = DailyTimeRecords::where([
                ['biometric_id', '=', $employee->biometric_id],
                ['dtr_date', '=', $data->date],
            ])->first();


            // Continue with your existing logic
            $user = $request->user;

            // Prepare data for this specific date
            $adjustmentData = $cleanData;
            if ($employee->shifting === 1) {
                $adjustmentData['first_in'] = isset($cleanData['time_in']) ? Carbon::parse($cleanData['time_in'])->format('H:i:s') : null;
                $adjustmentData['first_out'] = isset($cleanData['time_out']) ? Carbon::parse($cleanData['time_out'])->format('H:i:s') : null;
                $adjustmentData['second_in'] = null;
                $adjustmentData['second_out'] = null;
            } else {
                $adjustmentData['first_in'] = isset($cleanData['time_in']) ? Carbon::parse($cleanData['time_in'])->format('H:i:s') : null;
                $adjustmentData['first_out'] = isset($cleanData['break_out']) ? Carbon::parse($cleanData['break_out'])->format('H:i:s') : null;
                $adjustmentData['second_in'] = isset($cleanData['break_in']) ? Carbon::parse($cleanData['break_in'])->format('H:i:s') : null;
                $adjustmentData['second_out'] = isset($cleanData['time_out']) ? Carbon::parse($cleanData['time_out'])->format('H:i:s') : null;
            }

            // Update the time adjustment for this date
            $data->daily_time_record_id = $daily_time_record->id;
            $data->first_in = $adjustmentData['first_in'];
            $data->first_out = $adjustmentData['first_out'];
            $data->second_in = $adjustmentData['second_in'];
            $data->second_out = $adjustmentData['second_out'];
            $data->remarks = $adjustmentData['remarks'];
            $data->update();

            $dtr = DailyTimeRecords::find($data->daily_time_record_id);
            if ($dtr !== null) {
                $firstInTime = Carbon::parse($data['date'] . " " . $data->first_in);
                $firstOutTime = Carbon::parse($data['date'] . " " . $data->first_out);
                $secondInTime = Carbon::parse($data['date'] . " " . $data->second_in);
                $secondOutTime = Carbon::parse($data['date'] . " " . $data->second_out);

                // Check if `first_in` is greater than `first_out`, indicating a date span
                if ($firstInTime->gt($firstOutTime)) {
                    $firstOutTime->addDay();
                }

                // Update existing DTR with correctly adjusted times
                $dtr->update([
                    'first_in' => $data->first_in === null ? null : $firstInTime->toDateTimeString(),
                    'first_out' => $data->first_out === null ? null : $firstOutTime->toDateTimeString(),
                    'second_in' => $data->second_in === null ? null : $secondInTime->toDateTimeString(),
                    'second_out' => $data->second_out === null ? null : $secondOutTime->toDateTimeString(),
                    'is_time_adjustment' => true
                ]);

                $month = Carbon::parse($dtr->dtr_date)->month;
                $year = Carbon::parse($dtr->dtr_date)->year;

                $dtrHelper = new DTRcontroller();
                $dtrHelper->RecomputeHours($dtr->biometric_id, $month, $year, $dtr->dtr_date);

                $dtr->is_time_adjustment = true;
                $dtr->update();
            }
            // Log system actions
            Helpers::registerSystemLogs($request, $data['id'], true, 'Success in creating time adjustment for ' . $data['date'] . '.');
            Helpers::registerTimeAdjustmentLogs($data->id, $user->id, 'update');

            return response()->json([
                'data' => new TimeAdjustmentResource($data),
                'message' => 'Time adjustments successfully created for the date range.'
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
