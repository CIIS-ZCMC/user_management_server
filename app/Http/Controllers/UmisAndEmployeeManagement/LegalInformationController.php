<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\LegalInformationManyRequest;
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
    
    public function storeMany($personal_information_id, LegalInformationManyRequest $request)
    {
        try{
            $success = [];

            foreach($request->legal_information as $legal_info){
                $cleanData = [];
                $cleanData['personal_information_id'] = $personal_information_id;
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
                    continue;
                }

                $success[] = $legal_info;
            }

            return $success;
        }catch(\Throwable $th){
            throw new \Exception("Failed to register employee legal information.", 400);
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
            $success = [];

            foreach($request->legal_information as $legal_info){
                $cleanData = [];
                foreach ($legal_info as $key => $value) {
                    if($key === 'id' && $value === null) continue;
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

                if($legal_info->id === null || $legal_info->id === 'null'){
                    $cleanData['personal_information_id'] = $id;
                    $legal_info = LegalInformation::create($cleanData);
                    if(!$legal_info) continue;
                    $success[] = $legal_info;
                    continue;
                }

                if(!$legal_info){
                    continue;
                }

                $legal_info = LegalInformation::find($legal_info->id);
                $legal_info->update($cleanData);

                $success[] = $legal_info;
            }

            return $success;
        }catch(\Throwable $th){
            throw new \Exception("Failed to register employee legal information.", 400);
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
