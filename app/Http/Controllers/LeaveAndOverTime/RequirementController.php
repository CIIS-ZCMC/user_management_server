<?php

namespace App\Http\Controllers\LeaveAndOverTime;

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
    public function index(Request $request)
    {
        try{
            $leave_requirements = Requirement::all();

            return response()->json([
                'data' => LeaveRequirementResource::collection($leave_requirements),
                'message' => 'Retrieve leave requirement records.'
            ],Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], 500);
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
                return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
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
                'employee_profile_id' => $employee_profile->id,
                'action' => 'Register new leave requirement.'
            ]);

            return response()->json([
                'data' => new LeaveRequirementResource($leave_requirement),
                'message' => 'Requirement has been sucessfully saved'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
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
            return response()->json(['message' => $th->getMessage()], 500);
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
                return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
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
                'employee_profile_id' => $employee_profile->id,
                'action' => 'Update leave requirement.'
            ]);

            return response()->json([
                'data' => new LeaveRequirementResource($leave_requirement),
                'message' => 'Requirement has been sucessfully saved'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
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
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
}
