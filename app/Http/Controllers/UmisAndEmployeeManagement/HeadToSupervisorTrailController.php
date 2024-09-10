<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Resources\HeadToSupervisorTrailResource;
use App\Models\HeadToSupervisorTrail;
use App\Models\EmployeeProfile;

class HeadToSupervisorTrailController extends Controller
{
    private $CONTROLLER_NAME = 'HeadToSupervisorTrail Module';
    private $PLURAL_MODULE_NAME = 'head to supervisor trail modules';
    private $SINGULAR_MODULE_NAME = 'head to supervisor trail module';

    public function index(Request $request)
    {
        try{
            $head_to_supervisor_trails = HeadToSupervisorTrail::all();

            return response()->json(['data' => HeadToSupervisorTrailResource::collection($head_to_supervisor_trails), 'message' => 'Record of employee assigned area trail history retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID(Request $request)
    {
        try{
            $employe_profile = EmployeeProfile::where('employee_id')->first();

            if(!$employe_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }


            $head_to_supervisor_trail = HeadToSupervisorTrail::where('employee_profile_id',$employe_profile['id'])->first();

            if(!$head_to_supervisor_trail)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new HeadToSupervisorTrailResource($head_to_supervisor_trail), 'message' => 'Employee assigned area record trail found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $head_to_supervisor_trail = HeadToSupervisorTrail::find($id);

            if(!$head_to_supervisor_trail)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new HeadToSupervisorTrailResource($head_to_supervisor_trail), 'message' => 'Assigned area record trail found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $head_to_supervisor_trail = HeadToSupervisorTrail::findOrFail($id);

            if(!$head_to_supervisor_trail)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $head_to_supervisor_trail->delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Assigned area record trail deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
