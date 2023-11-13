<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\RequestLogger;
use App\Http\Requests\OtherInformationRequest;
use App\Http\Resources\OtherInformationResource;
use App\Models\OtherInformation;
use App\Models\EmployeeProfile;
use App\Models\SystemLogs;

class OtherInformationController extends Controller
{
    private $CONTROLLER_NAME = 'Other Information';
    private $PLURAL_MODULE_NAME = 'other informations';
    private $SINGULAR_MODULE_NAME = 'other information';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $other_information = OtherInformation::where('personal_information_id',$id)->get();

            if(!$other_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching employee '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => OtherInformationResource::collection($other_information)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByPersonalInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID($id, Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee_profile->personalInformation;
            $other_information = $personal_information;

            $this->registerSystemLogs($request, $id, true, 'Success in fetching employee '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => OtherInformationResource::collection($other_information),
                'message' => 'Employee other information retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(OtherInfomrationRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $other_information = OtherInformation::create($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new OthereInformationResource($other_information),
                'message' => 'New employee other information registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $other_information = OtherInformation::find($id);

            if(!$other_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new OtherInformationResource($other_information),
                'message' => 'Other information retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, OtherInfomrationRequest $request)
    {
        try{
            $other_information = OtherInformation::find($id);

            if(!$other_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $other_information -> update($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new OthereInformationResource($other_information),
                'message' => 'Other information record updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $other_information = OtherInformation::findOrFail($id);

            if(!$other_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $other_information -> delete();

            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Other information record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByPersonalInformationID($id, Request $request)
    {
        try{
            $other_information = OtherInformation::where('personal_information_id', $id)->get();

            if(!$other_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $other_information -> delete();

            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Other information record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroyByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByEmployeeID($id, Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee_profile->personalInformationl;
            $other_information = $personal_information->otherInformation;
            $other_information -> delete();

            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Other information record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroyByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function registerSystemLogs($request, $moduleID, $status, $remarks)
    {
        $ip = $request->ip();
        $user = $request->user;
        $permission = $request->permission;
        list($action, $module) = explode(' ', $permission);

        SystemLogs::create([
            'employee_profile_id' => $user->id,
            'module_id' => $moduleID,
            'action' => $action,
            'module' => $module,
            'status' => $status,
            'remarks' => $remarks,
            'ip_address' => $ip
        ]);
    }
}
