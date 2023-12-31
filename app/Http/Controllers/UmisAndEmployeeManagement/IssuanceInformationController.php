<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\PasswordApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Services\RequestLogger;
use App\Http\Requests\IssuanceInformationRequest;
use App\Http\Resources\IssuanceInformationResource;
use App\Models\IssuanceInformation;

class IssuanceInformationController extends Controller
{
    private $CONTROLLER_NAME = 'Issuance Information';
    private $PLURAL_MODULE_NAME = 'issuance informations';
    private $SINGULAR_MODULE_NAME = 'issuance information';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    public function store(IssuanceInformationRequest $request)
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

            $issuance_information = IssuanceInformation::create($cleanData);

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
           
            return response()->json([
                'data' => new IssuanceInformationResource($issuance_information) ,
                'message' => 'Newly added employee issuance information.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $issuance_information = IssuanceInformation::findOrFail($id);

            if(!$issuance_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new IssuanceInformation($issuance_information), 'message' => 'Employee issuance information found.' ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, IssuanceInformationRequest $request)
    {
        try{
            $issuance_information = IssuanceInformation::find($id);

            if(!$issuance_information)
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

            $issuance_information -> update($cleanData); 

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new IssuanceInformationResource($issuance_information) ,'message' => 'Updated employee issuance information.'], Response::HTTP_OK);
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

            $issuance_information = IssuanceInformation::findOrFail($id);

            if(!$issuance_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $issuance_information -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
           
            return response()->json(['message' => 'employee issuance information deleted'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
