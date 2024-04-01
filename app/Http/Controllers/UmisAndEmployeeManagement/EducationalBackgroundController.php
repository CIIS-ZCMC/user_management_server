<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\EducationalBackgroundManyRequest;
use App\Http\Requests\PasswordApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\EducationalBackgroundRequest;
use App\Http\Resources\EducationalBackgroundResource;
use App\Models\EducationalBackground;
use App\Models\EmployeeProfile;

class EducationalBackgroundController extends Controller
{
    private $CONTROLLER_NAME = 'EducationalBackground';
    private $PLURAL_MODULE_NAME = 'educational_backgrounds';
    private $SINGULAR_MODULE_NAME = 'educational_background';

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $educational_backgrounds = EducationalBackground::where('personal_information_id', $id)->get();

            if(!$educational_backgrounds)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => EducationalBackgroundResource::collection($educational_backgrounds), 
                'message' => 'Employee educational record found.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID(Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::where('employee_id',$request->input('employee_id'))->get();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $personal_information = $employee_profile->personalInformation;
            $educational_backgrounds = $personal_information->educational_backgrounds;

            return response()->json([
                'data' => EducationalBackgroundResource::collection($educational_backgrounds), 
                'message' => 'Employee educational record found.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(EducationalBackgroundRequest $request)
    {
        
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $educational_background = EducationalBackground::create($cleanData);

            Helpers::registerSystemLogs($request, $educational_background['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
           
            return response()->json([
                'data' => new EducationalBackgroundResource($educational_background),
                'message' => 'New employee education background registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function storeMany($personal_information_id, $educations)
    {
        try{
            $success = [];

            foreach($educations as $education){
                $cleanData = [];
                foreach ($education as $key => $value) {
                    if ($value === null) {
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($value);
                }
                $cleanData['$personal_information_id'] = $personal_information_id;
                $educational_background = EducationalBackground::create($cleanData);
                
                if(!$educational_background){
                    continue;
                }

                $success[] = $educational_background;
            }

            return $success;
        }catch(\Throwable $th){
            throw new \Exception("Failed to register employee education record.", 400);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $educational_background = EducationalBackground::findOrFail($id);

            if(!$educational_background)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new EducationalBackgroundResource($educational_background), 'message' => 'Educational record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, EducationalBackgroundRequest $request)
    {
        try{
            $educational_background = EducationalBackground::find($id);

            if(!$educational_background)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $educational_background->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new EducationalBackgroundResource($educational_background), 'message' => 'Employee educational background data has been updated.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function updateMany(EducationalBackgroundManyRequest $request)
    {
        try{

            $cleanData  = [];
            $failed = [];
            $success = [];

            foreach($request->educations as $education){
                $cleanNewData = [];
                foreach($education as $key => $fields){
                    if($fields === null || $fields === 'null'){
                        $cleanNewData[$key] = $fields;
                        continue;
                    }
                    $cleanNewData[$key] = strip_tags($fields);
                }
                $cleanData[] = $cleanNewData;
            }

            foreach ($cleanData as $key => $education) {
                $educational_background = EducationalBackground::find($education->id);

                if(!$educational_background)
                {
                    $failed[] = $education;
                    continue;
                }

                $educational_background->update($cleanData);
                $success[] = $educational_background;
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            if(count($cleanData) === count($failed)){
                return response()->json([
                    'message' => "Request to update educations has failed.",
                    'failed' => $failed
                ], Response::HTTP_BAD_REQUEST);
            }

            if(count($failed) > 0 && count($success) > count($failed)){
                return response()->json([
                    'data' => EducationalBackgroundResource::collection($success), 
                    'failed' => $failed,
                    'message' => "Successfully update some education background record.",
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => EducationalBackgroundResource::collection($success),
                'message' => 'Employee educational_background data is updated.'
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

            $educational_background = EducationalBackground::findOrFail($id);

            if(!$educational_background)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $educational_background->delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee educational_background record deleted.'], Response::HTTP_OK);
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

            $educational_backgrounds = EducationalBackground::where('personal_information_id', $id)->get();

            if(!$educational_backgrounds)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($educational_backgrounds as $key => $educational_background){
                $educational_background->delete();
            }
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee educational_backgrounds record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByEmployeeID(AuthPinApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $employee_profile = EmployeeProfile::where('employee_id',$request->input('employee_id'))->get();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee_profile->personalInformation;
            $educational_backgrounds = $personal_information->educational_backgrounds;

            foreach($educational_backgrounds as $key => $educational_background){
                $educational_background->delete();
            }
            
            Helpers::registerSystemLogs($request, null, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee educational_backgrounds record deleted'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
