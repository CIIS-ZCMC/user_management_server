<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\OtherInformationManyRequest;
use App\Http\Requests\PasswordApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\OtherInformationRequest;
use App\Http\Resources\OtherInformationResource;
use App\Models\OtherInformation;
use App\Models\EmployeeProfile;

class OtherInformationController extends Controller
{
    private $CONTROLLER_NAME = 'Other Information';
    private $PLURAL_MODULE_NAME = 'other informations';
    private $SINGULAR_MODULE_NAME = 'other information';

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $other_information = OtherInformation::where('personal_information_id',$id)->get();

            if(!$other_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => OtherInformationResource::collection($other_information)], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByPersonalInformation', $th->getMessage());
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
            $other_information = $personal_information;

            return response()->json([
                'data' => OtherInformationResource::collection($other_information),
                'message' => 'Employee other information retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(OtherInformationRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $other_information = OtherInformation::create($cleanData);

            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new OtherInformationResource($other_information),
                'message' => 'New employee other information registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function storeMany($personal_information_id, $others)
    {
        try{
            $success = [];

            foreach($others as $other){
                $cleanData = [];
                $cleanData['personal_information_id'] = $personal_information_id;
                foreach ($other as $key => $value) {
                    if ($value === null) {
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($value);
                }

                $other_information = OtherInformation::create($cleanData);

                if(!$other){
                    continue;
                }

                $success[] = $other_information;
            }
            
            return $success;
        }catch(\Throwable $th){
            throw new \Exception("Failed to register employee other information.", 400);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $other_information = OtherInformation::find($id);

            if(!$other_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new OtherInformationResource($other_information),
                'message' => 'Other information retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, OtherInformationRequest $request)
    {
        try{
            $other_information = OtherInformation::find($id);

            if(!$other_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $other_information -> update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new OtherInformationResource($other_information),
                'message' => 'Other information record updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function updateMany(OtherInformationManyRequest $request)
    {
        try{
            $cleanData  = [];
            $failed = [];
            $success = [];

            foreach($request->others as $other){
                $cleanNewData = [];
                foreach($other as $key => $fields){
                    if($fields === null || $fields === 'null'){
                        $cleanNewData[$key] = $fields;
                        continue;
                    }
                    $cleanNewData[$key] = strip_tags($fields);
                }
                $cleanData[] = $cleanNewData;
            }

            foreach ($cleanData as $key => $others) {
                $other_new = OtherInformation::find($others->id);

                if(!$other_new)
                {
                    $failed[] = $others;
                    continue;
                }

                $other_new->update($cleanData);
                $success[] = $other_new;
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            if(count($cleanData) === count($failed)){
                return response()->json([
                    'message' => "Request to update other information records has failed.",
                    'failed' => $failed
                ], Response::HTTP_BAD_REQUEST);
            }

            if(count($failed) > 0 && count($success) > count($failed)){
                return response()->json([
                    'data' => OtherInformationResource::collection($success), 
                    'failed' => $failed,
                    'message' => "Successfully update some other information record.",
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => OtherInformationResource::collection($success),
                'message' => 'Employee other information data is updated.'
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

            $other_information = OtherInformation::findOrFail($id);

            if(!$other_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $other_information -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Other information record deleted.'], Response::HTTP_OK);
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

            $other_information = OtherInformation::where('personal_information_id', $id)->get();

            if(!$other_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $other_information -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Other information record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroyByPersonalInformationID', $th->getMessage());
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

            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee_profile->personalInformationl;
            $other_information = $personal_information->otherInformation;
            $other_information -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Other information record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroyByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
