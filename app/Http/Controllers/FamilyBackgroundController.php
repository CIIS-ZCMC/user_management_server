<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\RequestLogger;
use App\Http\Requests\FamilyBackgroundRequest;
use App\Http\Resources\FamilyBackgroundResource;
use App\Models\FamilyBackground;
use App\Models\EmployeeProfile;
use App\Models\SystemLogs;

class FamilyBackgroundController extends Controller
{
    private $CONTROLLER_NAME = 'Legal Information Question Controller';
    private $PLURAL_MODULE_NAME = 'family backgrounds';
    private $SINGULAR_MODULE_NAME = 'family background';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    public function findByEmployeeID($id, Request $request)
    {
        try{
            $employee = EmployeeProfile::find($id);

            if(!$employee)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee->personalInformation;


            $family_background = FamilyBackground::where('personal_information_id',$personal_information['id'])->first();

            if(!$family_background)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id , true, 'Success in fetching employee '.$SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new FamilyBackgroundResource($family_background),'message' => 'Employee family background record retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'familyBackGroundEmployee', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $family_background = FamilyBackground::where('personal_information_id',$id)->first();

            if(!$family_background)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id , true, 'Success in fetching employee '.$SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new FamilyBackgroundResource($family_background),'message' => 'Employee family background record retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'familyBackGroundPersonalInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(FamilyBackgroundRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'tin_no' || $key === 'rdo_no'){
                    $cleanData[$key] = $this->encryptData($value);
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $family_background = FamilyBackground::create($cleanData);
            
            $this->registerSystemLogs($request, $family_background['id'], true, 'Success in creating '.$SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new FamilyBackgroundResource($family_background) ,'message' => 'New family background registered.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $family_background = FamilyBackground::findOrFail($id);

            if(!$family_background)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new FamilyBackgroundResource($family_background), 'message' => 'Family background record retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, FamilyBackgroundRequest $request)
    {
        try{
            $family_background = FamilyBackground::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'tin_no' || $key === 'rdo_no'){
                    $cleanData[$key] = $this->encryptData($value);
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $family_background -> update($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in updating '.$SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new FamilyBackgroundResource($family_background) ,'message' => 'Employee family background details updated.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $family_background = FamilyBackground::findOrFail($id);

            if(!$family_background)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $family_background -> delete();
            
            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee family background record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByPersonalInformationID($id, Request $request)
    {
        try{
            $family_background = FamilyBackground::where('personal_information_id',$id)->first();

            if(!$family_background)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $family_background -> delete();

            $this->registerSystemLogs($request, $id, true, 'Success in deleting employee '.$SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee family background record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroyPersonalInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByEmployeeID($id, Request $request)
    {
        try{
            $employee = EmployeeProfile::find($id);

            if(!$employee)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee->personalInformation;

            $family_background = $personal_information->familyBackground;
            $family_background -> delete();

            $this->registerSystemLogs($request, $id, true, 'Success in deleting employee '.$SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee family background record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroyEmployee', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function encryptData($dataToEncrypt)
    {
        return openssl_encrypt($dataToEncrypt, env("ENCRYPT_DECRYPT_ALGORITHM"), env("DATA_KEY_ENCRYPTION"), 0, substr(md5(env("DATA_KEY_ENCRYPTION")), 0, 16));
    }

    protected function infoLog($module, $message)
    {
        Log::channel('custom-info')->info('Family Background Controller ['.$module.']: message: '.$errorMessage);
    }

    protected function errorLog($module, $errorMessage)
    {
        Log::channel('custom-error')->error('Family Background Controller ['.$module.']: message: '.$errorMessage);
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
