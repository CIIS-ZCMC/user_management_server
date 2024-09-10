<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\ReferenceManyRequest;
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
    
    public function storeMany($personal_information_id, ReferenceManyRequest $request)
    {
        try{
            $success = [];

            foreach($request->reference as $reference){
                $cleanData = [];
                $cleanData['personal_information_id'] = $personal_information_id;
                foreach ($reference as $key => $value) {
                    $cleanData[$key] = strip_tags($value);
                }
                $reference = Reference::create($cleanData);

                if(!$reference){
                    continue;
                }

                $success[] = $reference;
            }
            
            return $success;
        }catch(\Throwable $th){
            throw new \Exception("Failed to register employee reference record.", 400);
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
    
    public function update($id, ReferenceManyRequest $request)
    {
        try{
            $success = [];

            foreach($request->references as $reference){
                $cleanData = [];
                foreach ($reference as $key => $value) {
                    if($key === 'id' && $value === null) continue;
                    $cleanData[$key] = strip_tags($value);
                }
                
                if($reference->id === null || $reference->id === 'null'){
                    $cleanData['personal_information_id'] = $id;
                    $reference = Reference::create($cleanData);
                    if(!$reference) continue;
                    $success[] = $reference;
                    continue;
                }

                $reference_data = Reference::find($reference->id);
                $reference_data->update($cleanData);
                $success[] = $reference_data;
            }
            
            return $success;
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, "updates", $th->getMessage());
            throw new \Exception("Failed to register employee reference record.", 400);
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
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
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
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
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
