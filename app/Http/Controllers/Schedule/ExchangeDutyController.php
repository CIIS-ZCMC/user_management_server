<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Resources\EmployeeScheduleResource;
use App\Models\EmployeeSchedule;
use App\Models\ExchangeDuty;

use App\Http\Resources\ExchangeDutyResource;
use App\Http\Requests\ExchangeDutyRequest;
use App\Helpers\Helpers;

use App\Models\TimeShift;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class ExchangeDutyController extends Controller
{
    private $CONTROLLER_NAME = 'Exchange Duty';
    private $PLURAL_MODULE_NAME = 'exchange duties';
    private $SINGULAR_MODULE_NAME = 'exchange duty';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user;

            if ($user->employee_id === "1918091351") {
                $model = ExchangeDuty::all();
            } else {
                $model = ExchangeDuty::where('requested_employee_id', $user->id)
                    ->Orwhere('approving_officer', $user->id)
                    ->where('deleted_at', null)
                    ->get();
            }

            return response()->json([
                'data' => ExchangeDutyResource::collection($model),
                // 'time_shift' => TimeShiftResource::collection(TimeShift::all())
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(Request $request)
    {
        try {

            $user = $request->user;
            $model = ExchangeDuty::where('requested_employee_id', $user->id)->get();
            return response()->json(['data' => ExchangeDutyResource::collection($model)], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExchangeDutyRequest $request)
    {
        try {
            $user = $request->user;
            $assigned_area = $user->assignedArea->findDetails();

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

            $data = null;
            $requester = $cleanData['requested_employee_id'];    // requester id on payload
            $reliever = $cleanData['reliever_employee_id'];      // reliever id on payload
            $date_swap = $cleanData['requested_date_to_swap'];       // date schedule of requester want to swap
            $date_duty = $cleanData['requested_date_to_duty'];       // date of requester want to duty

            $requester_schedule = EmployeeSchedule::where('employee_profile_id', $requester)
                ->whereHas('schedule', function ($query) use ($date_swap) {
                    $query->where('date', $date_swap);
                })->first();

            if (!$requester_schedule) {
                return response()->json(['message' => 'Employee requester has no schedule on ' . $date_swap . '.'], Response::HTTP_NOT_FOUND);
            }

            $reliever_schedule = EmployeeSchedule::where('employee_profile_id', $reliever)
                ->whereHas('schedule', function ($query) use ($date_duty) {
                    $query->where('date', $date_duty);
                })->first();

            if (!$reliever_schedule) {
                return response()->json(['message' => 'Employee reliever has no schedule on ' . $date_duty . '.'], Response::HTTP_NOT_FOUND);
            }

            $approve_by = Helpers::ExchangeDutyApproval($assigned_area, $user->id);

            $cleanData['approving_officer'] = $approve_by['approve_by'];

            $data = ExchangeDuty::create($cleanData);

            Helpers::registerSystemLogs($request, $data['id'], true, 'Success in creating.' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new ExchangeDutyResource($data),
                'logs' => Helpers::registerExchangeDutyLogs($data->id, $user->id, 'Applied'),
                'message' => 'Requested Swap Schedule.'
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function edit(Request $request)
    {
        try {
            $user = $request->user;

            $model = ExchangeDuty::where('reliever_employee_id', $user->id)
                ->where('deleted_at', null)
                ->get();

            return response()->json([
                'data' => ExchangeDutyResource::collection($model),
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;

            $data = ExchangeDuty::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            switch ($data->status) {
                case 'applied':
                    $data->update(['status' => $request->approval_status]);
                    break;

                case 'for head approval':
                    // Find the schedule of the requester
                    $requester = EmployeeSchedule::where('employee_profile_id', $data->requested_employee_id)
                        ->whereHas('schedule', function ($query) use ($data) {
                            $query->where('date', $data->requested_date_to_swap);
                        })->first();

                    // Find the schedule of the reliever
                    $reliever = EmployeeSchedule::where('employee_profile_id', $data->reliever_employee_id)
                        ->whereHas('schedule', function ($query) use ($data) {
                            $query->where('date', $data->requested_date_to_duty);
                        })->first();

                    // Update the requester's schedule with the reliever's employee profile ID
                    $requester->update(['employee_profile_id' => $data->reliever_employee_id]);  // Swap requester schedule to reliever

                    // Update the reliever's schedule with the requester's employee profile ID
                    $reliever->update(['employee_profile_id' => $data->requested_employee_id]); // swap reliever schedule to requester

                    $data->update(['status' => $request->approval_status, 'remarks' => $request->remarks, 'approval_date' => Carbon::now()]);
                    break;
            }

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in updating.' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new ExchangeDutyResource($data),
                'logs' => Helpers::registerExchangeDutyLogs($data->id, $user->id, $request->approval_status),
                'message' => 'Approved Complete.'
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
            $data = ExchangeDuty::withTrashed()->findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            if ($data->deleted_at != null) {
                $data->forceDelete();
            } else {
                $data->delete();
            }

            Helpers::registerSystemLogs($request, $id, true, 'Success in delete.' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => $data,
                'logs' => Helpers::registerExchangeDutyLogs($data->id, $request->user->id, 'Destroy'),
                'msg' => 'Request successfully deleted.'
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findMySchedule(Request $request)
    {
        try {
            $user = $request->user->id;

            $sql = EmployeeSchedule::where('employee_profile_id', $user)
                ->whereHas('schedule', function ($query) use ($request) {
                    $query->where('date', $request->date_selected);
                })->get();

            if ($sql->isEmpty()) {
                return response()->json(['message' => "Please select a date with schedule."], Response::HTTP_OK);
            }

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
            return $th;
            Helpers::errorLog($this->CONTROLLER_NAME, 'findSchedule', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findRelieverSchedule(Request $request)
    {
        try {
            $user = $request->user->id;
            $reliever_id = $request->employee_id;

            $user_schedule = EmployeeSchedule::where('employee_profile_id', $user)
                ->whereHas('schedule', function ($query) use ($request) {
                    $query->where('date', $request->date_selected);
                })->get();

            if ($user_schedule->isNotEmpty()) {
                return response()->json(['message' => "Your already have schedule on date:" . $request->date_selected], Response::HTTP_OK);
            }

            $sql = EmployeeSchedule::where('employee_profile_id', $reliever_id)
                ->whereHas('schedule', function ($query) use ($request) {
                    $query->where('date', $request->date_selected);
                })->get();

            if ($sql->isEmpty()) {
                return response()->json(['message' => "Reliever has no schedule on date: " . $request->date_selected], Response::HTTP_OK);
            }

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

}
