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
use App\Http\Requests\ReferenceRequest;
use App\Http\Resources\ReferenceResource;
use App\Models\Reference;
use App\Models\EmployeeProfile;

class ReferencesController extends Controller
{
    private $CONTROLLER_NAME = 'Reference';
    private $PLURAL_MODULE_NAME = 'references';
    private $SINGULAR_MODULE_NAME = 'reference';
    
    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $references = Reference::where('personal_information_id', $id)->get();

            if(!$references){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json([
                'data' => ReferenceResource::collection($references),
                'message' => 'Employee reference record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
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

            $references = $employee_profile->references;
            
            return response()->json([
                'data' => ReferenceResource::collection($references),
                'message' => 'Employee reference record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(ReferenceRequest $request)
    {
        try{
            $cleanData = [];
            
            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $reference = Reference::create($cleanData);
            
            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new ReferenceResource($reference),
                'message' => 'New reference registerd.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function storeMany(Request $request)
    {
        try{
            $success = [];
            $failed = [];
            $personal_information_id = strip_tags($request->personal_information_id);

            foreach(json_decode($request->references) as $reference){
                $cleanData = [];
                $cleanData['personal_information_id'] = $personal_information_id;
                foreach ($reference as $key => $value) {
                    $cleanData[$key] = strip_tags($value);
                }
                $reference = Reference::create($cleanData);

                if(!$reference){
                    $failed[] = $cleanData;
                    continue;
                }

                $success[] = $reference;
            }
            
            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            if(count($failed) > 0){
                return response()->json([
                    'data' => ReferenceResource::collection($success),
                    'failed' => $failed,
                    'message' => 'Some data failed to registere.'
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => ReferenceResource::collection($success),
                'message' => 'New reference registerd.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $reference = Reference::find($id);

            if(!$reference)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json([
                'data' => new ReferenceResource($reference),
                'message' => 'Reference record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, ReferenceRequest $request)
    {
        try{
            $reference = Reference::find($id);

            if(!$reference)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $reference -> update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new ReferenceResource($reference),
                'message' => 'Reference record updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_UNAUTHORIZED);
            }

            $reference = Reference::findOrFail($id);

            if(!$reference)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $reference -> delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Reference record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByPersonaslInformationID($id, AuthPinApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_UNAUTHORIZED);
            }

            $references = Reference::where('personal_information_id', $id)->get();

            if(!$references)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($references as $key => $value)
            {
                $value -> delete();
            }

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Reference record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroyByPersonaslInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByEmployeeID($id, AuthPinApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::where('employee_id', $id)->first();

            if($employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $personal_information = $employee_profile -> personalInformation;
            $references = $personal_information -> references;

            foreach($references as $key => $value)
            {
                $value -> delete();
            }

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Reference record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroyByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
