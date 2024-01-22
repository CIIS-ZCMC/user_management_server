<?php

namespace App\Http\Controllers\Schedule;

use App\Models\PullOut;
use App\Models\EmployeeProfile;
use App\Models\TimeAdjusment;

use App\Http\Resources\PullOutResource;
use App\Http\Requests\PullOutRequest;
use App\Helpers\Helpers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use DateTime;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PullOutController extends Controller
{
    private $CONTROLLER_NAME = 'Pull Out';
    private $PLURAL_MODULE_NAME = 'pull outs';
    private $SINGULAR_MODULE_NAME = 'pull out';
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            
            Helpers::registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');
            return response()->json(['data' => PullOutResource::collection(PullOut::all())], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }//
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
    public function store(PullOutRequest $request)
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

                if (is_array($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $user               = $request->user;
            $data               = null;
            $msg                = null;
            $approving_officer  = null;

            $selectedEmployeeIds = array_column($cleanData['employee'], 'employee_id');
            $employees = EmployeeProfile::whereIn('id', $selectedEmployeeIds)->get();

            foreach ($employees as $employee) {
                $employeeArea = $employee->assignedArea->findDetails();

                if ($employeeArea) {
                    switch ($employeeArea['sector']) {
                        case 'Division':
                            $approving_officer = $employee->assignedArea->division->divisionHead;
                            break;

                        case 'Department':
                            $approving_officer = $employee->assignedArea->department->head;
                            break;

                        case 'Section':
                            $approving_officer = $employee->assignedArea->department->supervisor;
                            break;

                        case 'Unit':
                            $approving_officer = $employee->assignedArea->department->head;
                            break;

                        default:
                            $approving_officer = 1;
                    }
                }

                $selectedEmployees[] = $employee;
            }

            $data = PullOut::create(array_merge($cleanData, ['requested_employee_id' => $user->id, 'approve_by_employee_id' => $approving_officer]));
        
            foreach ($selectedEmployees as $employee) {
                $query = DB::table('pull_out_employee')->where([
                    ['pull_out_id', '=', $data->id],
                    ['employee_profile_id', '=', $employee->id],
                ])->first();
        
                if ($query) {
                    $msg = 'Pull-out request already exists.';
                } else {
                    $data->employee()->attach($employee);
                    $msg = 'New pull-out requested.';
                }
            }

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
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
            $data = new PullOutResource(PullOut::findOrFail($id));

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
    public function edit(PullOut $pullOut)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PullOutRequest $request, $id)
    {
        try {
            
            $data = PullOut::findOrFail($id);

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

                if (is_array($value)) {
                    $cleanData[$key] = $value;
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
            $data = PullOut::withTrashed()->findOrFail($id);
         
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

            
            $data = PullOut::findOrFail($id);

            if(!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $query = PullOut::where('id', $data->id)->update([
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
  