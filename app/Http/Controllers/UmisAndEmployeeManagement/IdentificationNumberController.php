<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\PasswordApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\IdentificationNumberRequest;
use App\Http\Resources\IdentificationNumberResource;
use App\Models\IdentificationNumber;
use App\Models\EmployeeProfile;

class IdentificationNumberController extends Controller
{
    private $CONTROLLER_NAME = 'Identification Number';
    private $PLURAL_MODULE_NAME = 'divisions';
    private $SINGULAR_MODULE_NAME = 'division';
    
    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $identification = IdentificationNumber::where('personal_information_id',$id)->first();

            if(!$identification)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new IdentificationNumberResource($identification), 'message' => 'Employee identification number retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
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
            $identification = $personal_information->identification;

            return response()->json(['data' => new IdentificationNumberResource($identification), 'message' => 'Employee identification number retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(IdentificationNumberRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if( $value === 'null' || $value === null || $key === 'personal_information_id'){
                    $cleanData[$key] = $value;
                    continue;
                }
                try{
                    $cleanData[$key] =  $this->encryptData(strip_tags($value));
                }catch(\Throwable $th){
                    $cleanData[$key] = $value;
                }
            }

            $identification = IdentificationNumber::create($cleanData);

            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new IdentificationNumberResource($identification) ,
                "message" => 'New employee identification number registred.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $identification = IdentificationNumber::find($id);

            if(!$identification)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new IdentificationNumberResource($identification), 'message' => 'Identification number record retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, IdentificationNumberRequest $request)
    {
        try{
            $identification = IdentificationNumber::find($id);

            $cleanData = [];
            
            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'personal_information_id'){ 
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] =  $this->encryptData(strip_tags($value));
            }

            $identification -> update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new IdentificationNumberResource($identification), "message" => 'Employee Identification number updated.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
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

            $identification = IdentificationNumber::findOrFail($id);

            if(!$identification)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $identification -> delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Employee identification number record Deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByPersonalInformation($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $identification = IdentificationNumber::where('personal_information_id', $id)->first();

            if(!$identification)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $identification -> delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Employee identification number record Deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
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
            $identification = $personal_information->identification;
            $identification -> delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Employee identification number record Deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function encryptData($dataToEncrypt)
    {
        try{
            return openssl_encrypt($dataToEncrypt, env("ENCRYPT_DECRYPT_ALGORITHM"), env("DATA_KEY_ENCRYPTION"), 0, substr(md5(env("DATA_KEY_ENCRYPTION")), 0, 16));
        }catch(\Throwable $th){
            return $dataToEncrypt;
        }
    }
}
