<?php

namespace App\Http\Controllers\Schedule;

use App\Models\Department;
use App\Models\Division;
use App\Models\ExchangeDuty;
use App\Models\Schedule;
use App\Models\EmployeeProfile;

use App\Http\Resources\ExchangeDutyResource;
use App\Http\Requests\ExchangeDutyRequest;
use App\Models\Section;
use App\Models\Unit;
use App\Services\RequestLogger;
use App\Helpers\Helpers;

use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

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
            $user           = $request->user;
            $assigned_area  = $user->assignedArea->findDetails();

            $model = ExchangeDuty::with(['requestedEmployee' => function ($query) {
                $query->with(['assignedArea']);
            }])->whereHas('requestedEmployee', function ($query) use ($user, $assigned_area) {
                $query->whereHas('assignedArea', function ($innerQuery) use ($user, $assigned_area) {
                    $innerQuery->where([strtolower($assigned_area['sector']).'_id' => $user->assignedArea->id]);
                });
            })->get();

            return response()->json(['data' => ExchangeDutyResource::collection($model)], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(Request $request) {
        try {
            
            $user = $request->user;
            $model = ExchangeDuty::where('requested_employee_id', $user->id)->get();
            return response()->json(['data' => ExchangeDutyResource::collection($model)], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(ExchangeDutyRequest $request)
    {
        try {
            
            $user           = $request->user;
            $assigned_area  = $user->assignedArea->findDetails();

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

                $cleanData[$key] = strip_tags($value);
            }

            $data       = null;
            $schedule   = null;
            $date_from  =  Carbon::parse($cleanData['date_from']);
            $date_to    =  Carbon::parse($cleanData['date_to']);

            $reliever = EmployeeProfile::where('id', $cleanData['reliever_employee_id'])->first();
            
            // Get all date selected
            $current_date = $date_from->copy();
            $selected_dates = [];
            while ($current_date->lte($date_to)) {
                $selected_dates[] = $current_date->toDateString();
                $current_date->addDay();
            }

            foreach ($selected_dates as $key => $date) {
                $schedule = Schedule::where('date', $date)->first();
                
                $approve_by = Helpers::ExchangeDutyApproval($assigned_area, $user->id);

                $data = new ExchangeDuty;
                $data->schedule_id              = $schedule->id;
                $data->requested_employee_id    = $user->id;
                $data->reliever_employee_id     = $cleanData['reliever_employee_id'];
                $data->approve_by               = $approve_by['approve_by'];
                $data->reason                   = $cleanData['reason'];
                $data->save();
            }

            Helpers::registerSystemLogs($request, $data['id'], true, 'Success in creating.'.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        try {
            $data = new ExchangeDutyResource(ExchangeDuty::findOrFail($id));

            if(!$data)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            Helpers::registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $data = ExchangeDuty::findOrFail($id);

            if(!$data) {
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

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $data->update($cleanData);

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in updating.'.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data ,'message' => 'Success'], Response::HTTP_OK);

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
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
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in delete.'.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data], Response::HTTP_OK);
        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update Approval of Request
     */
    public function approve($id, Request $request) {
        try {

            $user = $request->user;

            $data = DB::table('exchange_duty_approval')->where([
                ['exchange_duty_id',    '=', $id],
                ['employee_profile_id', '=', $user->id],
            ])->first();

            if(!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $query = DB::table('exchange_duty_approval')->where('id', $data->id)->update([
                'approval_status' => $request->approval_status
            ]);

            Helpers::registerSystemLogs($request, $id, true, 'Success in approve '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $query, 'message' => 'Success'], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'approve', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
