<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Requests\OnCallRequest;
use App\Http\Resources\EmployeeProfileResource;
use App\Http\Resources\OnCallResource;
use App\Models\EmployeeProfile;
use App\Models\OnCall;

use App\Helpers\Helpers;

use Illuminate\Http\Response;

use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OnCallController extends Controller
{
    private $CONTROLLER_NAME = 'On Call Schedule';
    private $PLURAL_MODULE_NAME = 'on call schedules';
    private $SINGULAR_MODULE_NAME = 'on call schedule';
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user;
            $assigned_area = $user->assignedArea->findDetails();

            //Array
            $myEmployees = $user->myEmployees($assigned_area, $user);
            $employee_ids = collect($myEmployees)->pluck('id')->toArray();


            $model = OnCall::with([
                'employee' => function ($query) use ($employee_ids) {
                    $query->with('personalInformation')->whereIn('id', $employee_ids);
                }
            ])
                ->whereYear('date', '=', $request->year)
                ->whereMonth('date', '=', $request->month)
                ->get();

            return response()->json([
                'data' => OnCallResource::collection($model),
                'employee' => EmployeeProfileResource::collection($myEmployees),
            ], Response::HTTP_OK);

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
            $model = OnCall::where('employee_profile_id', $user->id)->get();
            return response()->json(['data' => OnCallResource::collection($model)], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OnCallRequest $request)
    {
        try {
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

                if (strtotime($value)) {
                    $datetime = Carbon::parse($value);
                    $cleanData[$key] = $datetime->format('Y-m-d'); // Adjust the format as needed
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $employee_on_calls = OnCall::where('employee_profile_id', $cleanData['employee_profile_id'])
                ->where('date', $cleanData['date'])
                ->first();

            if ($employee_on_calls) {
                return response()->json(['message' => "On Call Schedule already exist"], Response::HTTP_FOUND);
            }

            $data = OnCall::create($cleanData);

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new OnCallResource($data),
                // 'logs' => Helpers::registerEmployeeScheduleLogs($data->id, $request->user->id, 'Store'),
                'message' => 'New On Call Schedule has been added.',
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $data = OnCall::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

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

                if (strtotime($value)) {
                    $datetime = Carbon::parse($value);
                    $cleanData[$key] = $datetime->format('Y-m-d'); // Adjust the format as needed
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $employee_on_calls = OnCall::where('employee_profile_id', $cleanData['employee_profile_id'])
                ->where('date', $cleanData['date'])
                ->first();

            if ($employee_on_calls) {
                return response()->json(['message' => "On Call Schedule already exist"], Response::HTTP_FOUND);
            }

            $data->employee_profile_id = $cleanData['employee_profile_id'];
            $data->update();

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new OnCallResource($data),
                // 'logs' => Helpers::registerEmployeeScheduleLogs($data->id, $request->user->id, 'Store'),
                'message' => 'On Call Schedule is up to date.',
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, Request $request)
    {
        try {
            $data = OnCall::find($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $data->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in delete ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
