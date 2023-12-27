<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\PasswordApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Services\RequestLogger;
use App\Http\Requests\CivilServiceEligibilityRequest;
use App\Http\Resources\CivilServiceEligibilityResource;
use App\Models\CivilServiceEligibility;
use App\Models\EmployeeProfile;

class CivilServiceEligibilityController extends Controller
{
    private $CONTROLLER_NAME = 'Civil Service Eligibility';
    private $PLURAL_MODULE_NAME = 'civil service eligibilities';
    private $SINGULAR_MODULE_NAME = 'civil service eligibility';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $civil_service_eligibilities = EmployeeProfile::find($id);
            
            if(count($civil_service_eligibilities) === 0)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching employee '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => CivilServiceEligibilityResource::collection($civil_service_eligibilities),
                'message' => 'Employee civil service records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID($id, Request $request)
    {
        try{
            $employee_profile = CivilServiceEligibility::where('personal_information_id', $id)->get();
            
            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $personal_information = $employee_profile->personalInformation;
            $civil_service_eligibilities = $personal_information->civilServiceEligibility;
            
            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching employee '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => CivilServiceEligibilityResource::collection($civil_service_eligibilities)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(CivilServiceEligibilityRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null)
                {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $civil_service_eligibility = CivilServiceEligibility::create($cleanData);

            $this->requestLogger->registerSystemLogs($request, $civil_service_eligibility['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new CivilServiceEligibilityResource($civil_service_eligibility), 
                'message' => 'New civil service record added.',
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function storeMany(Request $request)
    {
        try{
            $success = [];
            $failed = [];
            $cleanData = [];

            foreach($request->civilserviceeligibilities as $civil_service_eligibility){
                foreach ($request->all() as $key => $value) {
                    if($value === null)
                    {
                        $cleanData[$key] = $value;
                        continue;
                    }
                }
                $cleanData[$key] = strip_tags($value);
                $civil_service_eligibility = CivilServiceEligibility::create($cleanData);

                if(!$civil_service_eligibility){
                    $failed[] = $cleanData;
                    continue;
                }

                $success = $civil_service_eligibility;
            }

            $this->requestLogger->registerSystemLogs($request, $civil_service_eligibility['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            if(count($failed) > 0){
                return response()->json([
                    'data' => CivilServiceEligibilityResource::collection($success),
                    'failed' => $failed,
                    'message' => 'Some data failed to registere.'
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => CivilServiceEligibilityResource::collection($success),
                'message' => 'New records created successfully.',
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $civil_service_eligibility = CivilServiceEligibility::find($id);

            if(!$civil_service_eligibility)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new CivilServiceEligibilityResource($civil_service_eligibility),
                'message' => 'Employee civil service record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, CivilServiceEligibilityRequest $request)
    {
        try{
            $civil_service_eligibility = CivilServiceEligibility::find($id);

            if(!$civil_service_eligibility)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null)
                {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $civil_service_eligibility -> update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new CivilServiceEligibilityResource($civil_service_eligibility),
                'message'=> 'Employee civil service details updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $civil_service_eligibility = CivilServiceEligibility::findOrFail($id);

            if(!$civil_service_eligibility)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $civil_service_eligibility -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByPersonalInformationID($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $civil_service_eligibilities = CivilServiceEligibility::where('personal_information_id',$id)->get();

            if(count($civil_service_eligibilities) === 0)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($civil_service_eligibilities as $key => $value)
            {
                $value -> delete();
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Employee civil service records deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroyByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByEmployeeID($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $personal_information = $employee_profile->personalInformation;
            $civil_service_eligibilities = $personal_information->civilServiceElibility;

            if(count($civil_service_eligibilities) === 0)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($civil_service_eligibilities as $key => $value)
            {
                $value -> delete();
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Employee civil service records deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroyByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
