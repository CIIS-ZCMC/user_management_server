<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\CivilServiceEligibilityManyRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\CivilServiceEligibilityRequest;
use App\Http\Resources\CivilServiceEligibilityResource;
use App\Models\CivilServiceEligibility;
use App\Models\EmployeeProfile;

class CivilServiceEligibilityController extends Controller
{
    private $CONTROLLER_NAME = 'Civil Service Eligibility';
    private $PLURAL_MODULE_NAME = 'civil service eligibilities';
    private $SINGULAR_MODULE_NAME = 'civil service eligibility';

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $civil_service_eligibilities = EmployeeProfile::find($id);
            
            if(count($civil_service_eligibilities) === 0)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => CivilServiceEligibilityResource::collection($civil_service_eligibilities),
                'message' => 'Employee civil service records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
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

            return response()->json(['data' => CivilServiceEligibilityResource::collection($civil_service_eligibilities)], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
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

            Helpers::registerSystemLogs($request, $civil_service_eligibility['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new CivilServiceEligibilityResource($civil_service_eligibility), 
                'message' => 'New civil service record added.',
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

            foreach(json_decode($request->civilserviceeligibilities) as $civil_service_eligibility){
                $cleanData = [];
                $cleanData['personal_information_id'] = $personal_information_id;
                foreach ($civil_service_eligibility as $key => $value) {
                    if($value === null)
                    {
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($value);
                }
                $civil_service_eligibility = CivilServiceEligibility::create($cleanData);

                if(!$civil_service_eligibility){
                    $failed[] = $cleanData;
                    continue;
                }

                $success[] = $civil_service_eligibility;
            }

            Helpers::registerSystemLogs($request, $civil_service_eligibility['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            if(count($failed) > 0){
                return response()->json([
                    'data' => CivilServiceEligibilityResource::collection($success),
                    'failed' => $failed,
                    'message' => 'Some data failed to register.'
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => CivilServiceEligibilityResource::collection($success),
                'message' => 'New records created successfully.',
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
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

            return response()->json([
                'data' => new CivilServiceEligibilityResource($civil_service_eligibility),
                'message' => 'Employee civil service record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
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

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new CivilServiceEligibilityResource($civil_service_eligibility),
                'message'=> 'Employee civil service details updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function updateMany(CivilServiceEligibilityManyRequest $request)
    {
        try{
            $cleanData  = [];
            $failed = [];
            $success = [];

            foreach($request->eligiblities as $eligiblity){
                $cleanNewData = [];
                foreach($eligiblity as $key => $fields){
                    if($fields === null || $fields === 'null'){
                        $cleanNewData[$key] = $fields;
                        continue;
                    }
                    $cleanNewData[$key] = strip_tags($fields);
                }
                $cleanData[] = $cleanNewData;
            }

            foreach ($cleanData as $key => $eligibilities) {
                $eligiblity_new = CivilServiceEligibility::find($eligibilities->id);

                if(!$eligiblity_new)
                {
                    $failed[] = $eligibilities;
                    continue;
                }

                $eligiblity_new->update($cleanData);
                $success[] = $eligiblity_new;
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            if(count($cleanData) === count($failed)){
                return response()->json([
                    'message' => "Request to update civil service eligibility records has failed.",
                    'failed' => $failed
                ], Response::HTTP_BAD_REQUEST);
            }

            if(count($failed) > 0 && count($success) > count($failed)){
                return response()->json([
                    'data' => CivilServiceEligibilityResource::collection($success), 
                    'failed' => $failed,
                    'message' => "Successfully update some civil service eligibility record.",
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => CivilServiceEligibilityResource::collection($success),
                'message' => 'Employee civil service eligibility data is updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'updateMany', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            // $user = $request->user;
            // $cleanData['pin'] = strip_tags($request->password);

            // if ($user['authorization_pin'] !==  $cleanData['pin']) {
            //     return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            // }

            $civil_service_eligibility = CivilServiceEligibility::findOrFail($id);

            if(!$civil_service_eligibility)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_FORBIDDEN);
            }

            $civil_service_eligibility -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByPersonalInformationID($id, AuthPinApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
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

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Employee civil service records deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroyByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByEmployeeID($id, PasswordApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
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

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Employee civil service records deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroyByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
