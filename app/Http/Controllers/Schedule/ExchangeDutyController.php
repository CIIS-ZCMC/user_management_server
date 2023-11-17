<?php

namespace App\Http\Controllers\Schedule;

use App\Models\ExchangeDuty;
use App\Models\Schedule;
use App\Models\EmployeeProfile;

use App\Http\Requests\ExchangeDutyRequest;
use App\Services\RequestLogger;
use App\Helpers\Helpers;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use DateTime;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class ExchangeDutyController extends Controller
{
    private $CONTROLLER_NAME = 'Exchange Duty';
    private $PLURAL_MODULE_NAME = 'exchange duties';
    private $SINGULAR_MODULE_NAME = 'exchange duty';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(ExchangeDutyRequest $request)
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

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            // $schedule = Schedule::where('id', $cleanData['schedule_id'])->first();

            // if ($check_schedule) {
            //     $reliever = EmployeeProfile::where('id', $cleanData['reliever_employee_id'])->first();

            //     if ($reliever) {
            //         $query = DB::table('employee_profile_schedule')->where([
            //             ['employee_profile_id', '=', $reliever->id],
            //             ['schedule_id', '=', $schedule->id],
            //         ])->first();
                    
            //     } else {
            //         return response()->json(['message' => 'No employee found.'], Response::HTTP_NOT_FOUND);
            //     }
            // } else {
            //     return response()->json(['message' => 'No schedule found.'], Response::HTTP_NOT_FOUND);
            // }
            
            
            $data = new ExchangeDuty;
            $data->schedule_id              = $cleanData['schedule_id'];
            $data->requested_employee_id    = $cleanData['requested_employee_id'];
            $data->reliever_employee_id     = $cleanData['reliever_employee_id'];
            $data->reason                   = $cleanData['reason'];
            $data->approve_by               = json_encode($cleanData['approve_by']);
            $data->created_at               = now();
            $data->updated_at               = now();
            $data->save();

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data ,'message' => 'Success'], Response::HTTP_OK);

        } catch (\Throwable $th) {
            
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(Request $request, $id)
    {
        try {
            $data = ExchangeDuty::findOrFail($id);

            $approve_by = json_decode($data->approve_by, true);
            foreach ($approve_by['approve_by'] as $key => $value) {
                if ($value['employee_id'] = $request['employee_id']) {
                    $value['approval_status_1'] = $request['approval_status_1'];
                }
            }
            
            $updatedJsonData = json_encode($approve_by);
            $data->update(['approve_by' => $updatedJsonData]);

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $approve_by ,'message' => 'Success'], Response::HTTP_OK);

        } catch (\Throwable $th) {
                        
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
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
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in delete '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data], Response::HTTP_OK);
        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
