<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\RequestLogger;
use App\Http\Requests\FamilyBackgroundRequest;
use App\Http\Resources\FamilyBackgroundResource;
use App\Models\FamilyBackground;
use App\Models\EmployeeProfile;

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

            $this->requestLogger->registerSystemLogs($request, $id , true, 'Success in fetching employee '.$this->SINGULAR_MODULE_NAME.'.');

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

            $this->requestLogger->registerSystemLogs($request, $id , true, 'Success in fetching employee '.$this->SINGULAR_MODULE_NAME.'.');

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
            
            $this->requestLogger->registerSystemLogs($request, $family_background['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

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

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

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

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

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
            
            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
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

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting employee '.$this->SINGULAR_MODULE_NAME.'.');
            
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

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting employee '.$this->SINGULAR_MODULE_NAME.'.');
            
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
}