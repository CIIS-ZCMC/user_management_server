<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Resources\TimeShiftResource;
use App\Models\EmployeeSchedule;
use App\Models\ExchangeDuty;
use App\Models\Schedule;
use App\Models\EmployeeProfile;

use App\Http\Resources\ExchangeDutyResource;
use App\Http\Requests\ExchangeDutyRequest;
use App\Helpers\Helpers;

use App\Models\TimeShift;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


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

            $model = ExchangeDuty::where('requested_employee_id', $user->id)
                ->Orwhere('approve_by', $user->id)
                ->where('deleted_at', null)
                ->get();

            return response()->json([
                'data' => ExchangeDutyResource::collection($model),
                'time_shift' => TimeShiftResource::collection(TimeShift::all())
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
                if (empty ($value)) {
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

            $employee = EmployeeProfile::where('id', $cleanData['reliever_employee_id'])->first();
            if ($employee) {
                $schedule = Schedule::where('time_shift_id', $cleanData['time_shift_id'])
                    ->where('date', $cleanData['date'])
                    ->first();

                if (!$schedule) {
                    $isWeekend = (Carbon::parse($cleanData['date']))->isWeekend();

                    $schedule = new Schedule;
                    $schedule->time_shift_id = $cleanData['time_shift_id'];
                    $schedule->date = $cleanData['date'];
                    $schedule->is_weekend = $isWeekend ? 1 : 0;
                    $schedule->save();
                }

                $employee_schedule = EmployeeSchedule::where('employee_profile_id', $employee->id)
                    ->where('schedule_id', $schedule->id)
                    ->first();

                if (!$employee_schedule) {
                    return response()->json(['message' => 'Employee reliever has no schedule on ' . $cleanData['date'] . '.'], Response::HTTP_NOT_FOUND);
                }

                $approve_by = Helpers::ExchangeDutyApproval($assigned_area, $user->id);

                $data = new ExchangeDuty;
                $data->schedule_id = $schedule->id;
                $data->requested_employee_id = $user->id;
                $data->reliever_employee_id = $employee->id;
                $data->approve_by = $approve_by['approve_by'];
                $data->reason = $cleanData['reason'];
                $data->save();
            }

            Helpers::registerSystemLogs($request, $data['id'], true, 'Success in creating.' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new ExchangeDutyResource($data),
                'logs' => Helpers::registerExchangeDutyLogs($data->id, $user->id, 'Applied'),
                'message' => 'Exchange Duty requested.'
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id, Request $request)
    {
        try {
            $data = ExchangeDuty::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . Cache::get('salt_value'), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_FORBIDDEN);
            }

            $status = null;
            if ($request->approval_status === 'approved') {
                switch ($data->status) {
                    case 'applied':
                        $status = 'approved';

                        EmployeeSchedule::where([
                            ['employee_profile_id', '=', $data->requested_employee_id],
                            ['schedule_id', '=', $data->schedule_id]
                        ])->update(['employee_profile_id' => $data->reliever_employee_id]);
                        break;

                    case 'declined':
                        $status = 'declined';
                        break;

                    default:
                        return null;
                }
            } else if ($request->approval_status === 'declined') {
                $status = 'declined';
            }

            $data->update(['status' => $status, 'remarks' => $request->remarks, 'approval_date' => Carbon::now()]);

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in updating.' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new ExchangeDutyResource($data),
                'logs' => Helpers::registerExchangeDutyLogs($data->id, $employee_profile->id, $status),
                'msg' => 'Approved Complete.'
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
}
