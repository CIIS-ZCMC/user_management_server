<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\LegalInformationRequest;
use App\Http\Resources\LegalInformationResource;
use App\Models\LegalInformation;

class LegalInformationController extends Controller
{
    private $CONTROLLER_NAME = 'Legal Information';
    private $PLURAL_MODULE_NAME = 'divisions';
    private $SINGULAR_MODULE_NAME = 'division';
    
    public function findByEmployeeID($id, Request $request)
    {
        try{
            $legal_informations = LegalInformation::where('employee_profile_id',$id)->get();

            if(!$legal_informations){
                return response()->json(['message' => 'No records found.'], 404);
            }

            return response()->json(['data' => LegalInformationResource::collection($legal_informations),'message' => 'Employee legal information record retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(LegalInformationRequest $request)
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
            
            return response()->json([
                'data' => new LegalInformationResource($legal_information) ,
                'message' => 'New employee legal information registered.'
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

            foreach(json_decode($request->legal_informations) as $legal_info){
                $cleanData = [];
                $cleanData['personal_information_id'] = intval($personal_information_id);
                foreach ($legal_info as $key => $value) {
                    if($value === null){
                        $cleanData[$key] = $value;
                        continue;
                    }
                    if($key === 'legal_iq_id') {
                        $cleanData[$key] = intval(strip_tags($value));
                        continue;
                    }
                    if($key === 'answer') {
                        $cleanData[$key] = intval(strip_tags($value)) === 1?true:false;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($value);
                }
                $legal_info = LegalInformation::create($cleanData);

                if(!$legal_info){
                    $failed[] = $cleanData;
                    continue;
                }

                $success[] = $legal_info;
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
                        
            if(count($failed) > 0){
                return response()->json([
                    'data' => LegalInformationResource::collection($success),
                    'failed' => $failed,
                    'message' => 'Some data failed to registere.'
                ], Response::HTTP_OK);
            }
            
            return response()->json([
                'data' => LegalInformationResource::collection($success) ,
                'message' => 'New employee legal information registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
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
            
            return response()->json(['data' => new LegalInformationResource($legal_information), 'message' => 'Legal information record retrieved'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, LegalInformationRequest $request)
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

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => new LegalInformationResource($legal_information) ,'message' => 'Legal information updated'], Response::HTTP_OK);
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
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $legal_information = LegalInformation::findOrFail($id);

            if(!$legal_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $legal_information->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Legal Information record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
