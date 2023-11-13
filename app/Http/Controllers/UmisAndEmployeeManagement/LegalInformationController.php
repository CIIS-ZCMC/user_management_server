<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\RequestLogger;
use App\Http\Requests\LegalInformationRequest;
use App\Http\Resources\LegalInformationResource;
use App\Models\LegalInformation;
use App\Models\SystemLogs;

class LegalInformationController extends Controller
{
    private $CONTROLLER_NAME = 'Legal Information';
    private $PLURAL_MODULE_NAME = 'divisions';
    private $SINGULAR_MODULE_NAME = 'division';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    public function findByEmployeeID($id, Request $request)
    {
        try{
            $legal_informations = LegalInformation::where('employee_profile_id',$id)->get();

            if(!$legal_informations){
                return response()->json(['message' => 'No records found.'], 404);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching employee '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => LegalInformationResource::collection($legal_informations),'message' => 'Employee legal information record retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(Request $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $legal_information = LegalInformation::create($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => new LegalInformation($legal_information) ,'message' => 'New employee legal information registered.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $legal_information = LegalInformation::find($id);

            if(!$legal_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => new LegalInformationResource($legal_information), 'message' => 'Legal information record retrieved'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, Request $request)
    {
        try{
            $legal_information = LegalInformation::find($id);

            if(!$legal_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'answer'){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $legal_information->update($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => new LegalInformationResource($legal_information) ,'message' => 'Legal information updated'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $legal_information = LegalInformation::findOrFail($id);

            if(!$legal_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $legal_information->delete();

            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Legal Information record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
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
