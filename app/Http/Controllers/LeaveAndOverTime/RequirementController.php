<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Helpers\Helpers;
use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Resources\LeaveTypeRequirementLogsResource;
use App\Models\LeaveTypeRequirementLog;
use App\Models\Requirement;
use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\RequirementRequest;
use App\Http\Resources\LeaveRequirementResource;
use App\Models\RequirementLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class RequirementController extends Controller
{

    private $CONTROLLER_NAME = "RequirementController";

    public function index(Request $request)
    {
        try{
            $leave_requirements = Requirement::all();

            return response()->json([
                'data' => LeaveRequirementResource::collection($leave_requirements),
                'message' => 'Retrieve leave requirement records.'
            ],Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RequirementRequest $request)
    {
        try{
            $employee_profile = $request->user;

            if(!$employee_profile){
                return response()->json(['message' => 'Unauthorized.'], Response::HTTP_FORBIDDEN);
            }

            $cleanData = [];

            foreach($request->all() as $key => $value){
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $leave_requirement = Requirement::create($cleanData);
       
            RequirementLog::create([
                'leave_requirement_id' => $leave_requirement->id,
                'action_by' => $employee_profile->id,
                'action' => 'Add'
            ]);
            return response()->json([
                'data' => new LeaveRequirementResource($leave_requirement),
                'message' => 'Requirement has been sucessfully saved'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id,Request $requirement)
    {
        try{
            $leave_requirements = Requirement::find($id);

            return response()->json([
                'data' => new LeaveRequirementResource($leave_requirements),
                'message' => 'Retrieve leave requirement records.'
            ],Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id, RequirementRequest $request)
    {
        try{
            $employee_profile = $request->user;

            if(!$employee_profile){
                return response()->json(['message' => 'Unauthorized.'], Response::HTTP_FORBIDDEN);
            }

            $leave_requirement = Requirement::find($id);

            $cleanData = [];

            foreach($request->all() as $key => $value){
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $leave_requirement->update($cleanData);

            RequirementLog::create([
                'leave_requirement_id' => $leave_requirement->id,
                'action_by' => $employee_profile->id,
                'action' => 'Update'
            ]);

            return response()->json([
                'data' => new LeaveRequirementResource($leave_requirement),
                'message' => 'Requirement has been sucessfully saved'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_requirements = Requirement::find($id);

            if(count($leave_requirements->leaveTypeRequirements) > 0){
                return response()->json(['message' => "Other records is using this leave requirement deletion is prohibited."], Response::HTTP_BAD_REQUEST);
            }

            RequirementLog::where('leave_requirement_id', $leave_requirements->id)->delete();

            return response()->json([
                'data' => new LeaveRequirementResource($leave_requirements),
                'message' => 'Retrieve leave requirement records.'
            ],Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
