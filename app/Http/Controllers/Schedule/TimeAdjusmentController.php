<?php

namespace App\Http\Controllers\Schedule;


use App\Models\EmployeeProfile;
use App\Models\TimeAdjusment;
use App\Models\DailyTimeRecords;

use App\Http\Resources\TimeAdjustmentResource;
use App\Http\Requests\TimeAdjustmentRequest;
use App\Helpers\Helpers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
Use DateTime;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
            
            Helpers::registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');
            return response()->json(['data' => TimeAdjustmentResource::collection(TimeAdjusment::all())], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
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
                if(empty($value)){
                    $cleanData[$key] = $value;
                    continue;
                }

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if(is_array($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $user               = $request->user;
            $data               = null;
            $approving_officer  = null;

            $dates = $cleanData['dates'];
            foreach ($dates as $key => $value) {
                $find = DailyTimeRecords::where([
                    ['biometric_id',    '=', $cleanData['biometric_id']],
                    ['dtr_date',        '=', $value['dtr_date']],
                ])->first();

                if ($find) {
                    $find_employee = EmployeeProfile::find($cleanData['employee_profile_id'])->first();
                    if ($find_employee) {
                        $employee_area =  $find_employee->assignedArea->findDetails();

                        switch ($employee_area['sector']) {
                            case 'Division':
                                $approving_officer = $find_employee->assignedArea->division->divisionHead;
                            break;
            
                            case 'Department':
                                $approving_officer = $find_employee->assignedArea->department->head;
                            break;
            
                            case 'Section':
                                $approving_officer = $find_employee->assignedArea->department->supervisor;
                            break;
            
                            case 'Unit':
                                $approving_officer = $employee_area->assignedArea->department->head;
                            break;
                            
                            default:
                                $approving_officer = 1;
                        }
                    }

                    $data = new TimeAdjusment;

                    $data->daily_time_record_id = $find->id;
                    $data->recommended_by       = $user->id;
                    $data->approve_by           = $approving_officer;
                    $data->employee_profile_id  = $cleanData['employee_profile_id'];
                    $data->remarks              = $cleanData['remarks'];
                    $data->first_in             = $value['first_in'];
                    $data->first_out            = $value['first_out'];
                    $data->second_in            = $value['second_in'];
                    $data->second_out           = $value['second_out'];
                    $data->save();
                }
            }

            Helpers::registerSystemLogs($request, $data['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data], Response::HTTP_OK);

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
            $data = new TimeAdjustmentResource(TimeAdjusment::findOrFail($id));

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
     * Show the form for editing the specified resource.
     */
    public function edit(TimeAdjusment $timeAdjusment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $data = TimeAdjusment::findOrFail($id);

            if(!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if(empty($value)){
                    $cleanData[$key] = $value;
                    continue;
                }

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if(is_array($value)) {
                    $section_data = [];

                    foreach ($request->all() as $key => $value) {
                        $section_data[$key] = $value;
                    }        
                    $cleanData[$key] = $section_data;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $data->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
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
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in delete '.$this->SINGULAR_MODULE_NAME.'.');
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
                if(empty($value)){
                    $cleanData[$key] = $value;
                    continue;
                }

                if (DateTime::createFromFormat('Y-m-d', $value)) {
                    $cleanData[$key] = Carbon::parse($value);
                    continue;
                }

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if(is_array($value)) {
                    $section_data = [];

                    foreach ($request->all() as $key => $value) {
                        $section_data[$key] = $value;
                    }        
                    $cleanData[$key] = $section_data;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            
            $data = TimeAdjusment::findOrFail($id);

            if(!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $query = TimeAdjusment::where('id', $data->id)->update([
                'status'            => $cleanData['status'],
                'approval_date'     => now(),
                'updated_at'        => now()
            ]);

            Helpers::registerSystemLogs($request, $id, true, 'Success in approve '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $query, 'message' => 'Success'], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'approve', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
