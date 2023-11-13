<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\RequestLogger;
use App\Http\Requests\EducationalBackgroundRequest;
use App\Http\Resources\EducationalBackgroundResource;
use App\Models\EducationalBackground;
use App\Models\EmployeeProfile;
use App\Models\SystemLogs;

class EducationalBackgroundController extends Controller
{
    private $CONTROLLER_NAME = 'EducationalBackground';
    private $PLURAL_MODULE_NAME = 'educational_backgrounds';
    private $SINGULAR_MODULE_NAME = 'educational_background';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $educational_backgrounds = EducationalBackground::where('personal_information_id', $id)->get();

            if(!$educational_background)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $educational_backgrounds['id'], true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => EducationalBackgroundResource::collection($educational_backgrounds), 'message' => 'Employee educational record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID(Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::where('employee_id',$request->input('employee_id'))->get();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $personal_information = $employee_profile->personalInformation;
            $educational_backgrounds = $personal_information->educational_backgrounds;

            $this->registerSystemLogs($request, $educational_backgrounds['id'], true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => EducationalBackgroundResource::collection($educational_backgrounds), 'message' => 'Employee educational record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(EducationalBackgroundRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $educational_background = EducationalBackground::create($cleanData);

            $this->registerSystemLogs($request, $educational_background['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new EducationalBackground($educational_background) ,'message' => 'New employee education background registered.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $educational_background = EducationalBackground::findOrFail($id);

            if(!$educational_background)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new EducationalBackground($educational_background), 'message' => 'Educational record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, EducationalBackgroundRequest $request)
    {
        try{
            $educational_background = EducationalBackground::find($id);

            if(!$educational_background)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $educational_background->update($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new EducationalBackgroundResource($educational_background), 'message' => 'Employee educational_background data is updated.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $educational_background = EducationalBackground::findOrFail($id);

            if(!$educational_background)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $educational_background->delete();
            
            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee educational_background record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByPersonalInformationID($id, Request $request)
    {
        try{
            $educational_backgrounds = EducationalBackground::where('personal_information_id', $id)->get();

            if(!$educational_backgrounds)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($educational_background as $key => $educational_background){
                $educational_background->delete();
            }
            
            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee educational_backgrounds record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByEmployeeID(Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::where('employee_id',$request->input('employee_id'))->get();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee_profile->personalInformation;
            $educational_backgrounds = $personal_information->educational_backgrounds;

            foreach($educational_background as $key => $educational_background){
                $educational_background->delete();
            }
            
            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee educational_backgrounds record deleted'], Response::HTTP_OK);
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
            'ip_educational_background' => $ip
        ]);
    }
}
