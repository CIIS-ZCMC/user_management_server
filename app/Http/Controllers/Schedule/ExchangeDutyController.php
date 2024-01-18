<?php

namespace App\Http\Controllers\Schedule;

use App\Models\Division;
use App\Models\ExchangeDuty;
use App\Models\Schedule;
use App\Models\EmployeeProfile;

use App\Http\Resources\ExchangeDutyResource;
use App\Http\Requests\ExchangeDutyRequest;
use App\Services\RequestLogger;
use App\Helpers\Helpers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

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

           

            return response()->json(['data' => ExchangeDutyResource::collection(ExchangeDuty::all())], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(Request $request) {
        try {
            
            $user           = $request->user;
            $assigned_area  = $user->assignedArea->findDetails();
            $head           = null;

            switch ($assigned_area['sector']) {
                case 'Division':
                    $head = $user->assignedArea->division->divisionHead;
                    // If employee is Division head
                    if(Division::find($assigned_area->details->id)->chief_employee_profile_id === $user->id){
                        $recommending_officer = $user->id;
                        $approving_office = $user->id;
                        break;
                    }
                break;

                case 'Department':
                    $head = $user->assignedArea->department->head;
                break;

                case 'Section':
                    $head = $user->assignedArea->department->supervisor;
                break;

                case 'Unit':
                    $head = $user->assignedArea->department->head;
                break;
                
                default:
                    $head = 1;
            }

            // return response()->json(['data' => ExchangeDutyResource::collection(ExchangeDuty::all())], Response::HTTP_OK);

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

            $schedule = Schedule::where('id', $cleanData['schedule_id'])->first();

            if ($schedule) {
                $reliever = EmployeeProfile::where('id', $cleanData['reliever_employee_id'])->first();

                if ($reliever) {
                    $query = DB::table('employee_profile_schedule')->where([
                        ['employee_profile_id', '=', $reliever->id],
                        ['schedule_id', '=', $schedule->id],
                    ])->first();

                    if ($query) {
                        $data = ExchangeDuty::create($cleanData);

                        $variable = $cleanData['approve_by'];
                        foreach ($variable as $key => $value) {
                            $approve_by = EmployeeProfile::select('id')->where('id', $value['employee_id'])->first();

                            if (!$approve_by) {
                                $msg = 'No Employee Found (Approve By)';

                            } else { 

                                $data->approval()->attach($approve_by);
                                $msg = 'New exchange duty requested.';
                            }
                        }
                    }
                    
                } else {
                    return response()->json(['message' => 'No employee found.'], Response::HTTP_NOT_FOUND);
                }
            } else {
                return response()->json(['message' => 'No schedule found.'], Response::HTTP_NOT_FOUND);
            }
           
            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating.'.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data ,'message' => $msg], Response::HTTP_OK);

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
    public function approve(Request $request, $id) {
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
            $user = $request->user;
            $data = DB::table('exchange_duty_approval')->where([
                ['exchange_duty_id',    '=', $id],
                ['employee_profile_id', '=', $user->id],
            ])->first();

            if(!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $query = DB::table('exchange_duty_approval')->where('id', $data->id)->update([
                'approval_status' => $cleanData['approval_status']
            ]);

            Helpers::registerSystemLogs($request, $id, true, 'Success in approve '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $query, 'message' => 'Success'], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'approve', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
