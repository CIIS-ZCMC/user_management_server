<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\VoluntaryWorkManyRequest;
use App\Http\Requests\WorkExperienceManyRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\WorkExperienceRequest;
use App\Http\Resources\WorkExperienceResource;
use App\Models\WorkExperience;
use App\Models\EmployeeProfile;

class WorkExperienceController extends Controller
{
    private $CONTROLLER_NAME = 'Work Experience';
    private $PLURAL_MODULE_NAME = 'work experiences';
    private $SINGULAR_MODULE_NAME = 'work experience';

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $work_experience =  WorkExperience::where('personal_information_id', $id)->get();

            if(!$work_experience)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json([
                'data' => WorkExperienceResource::collection($work_experience),
                'message' => 'Employee work experience record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID($id, Request $request)
    {
        try{
            $employee_profile =  EmployeeProfile::find($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee_profile->personalInformation;
            $work_experience = $personal_information->workExperience;
            
            return response()->json([
                'data' => WorkExperienceResource::collection($work_experience),
                'message' => 'Employee work experience record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(WorkExperienceRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value===null){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = $value;
            }

            $work_experience = WorkExperience::create($cleanData);

            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new WorkExperienceResource($work_experience),
                'message' => 'New work experience added.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function storeMany($personal_information_id, WorkExperienceRequest $request)
    {
        try{
            $success = [];

            foreach($request->work_experiences as $work_experience){
                $cleanData = [];
                foreach ($work_experience as $key => $value) {
                    if($value===null){
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = $value;
                }
                $cleanData['personal_information_id'] = $personal_information_id;
                $work_experience = WorkExperience::create($cleanData);
                
                if(!$work_experience){
                    continue;
                }

                $success[] = $work_experience;
            }

            return $success;
        }catch(\Throwable $th){
            throw new \Exception("Failed to register employee work experience.", 400);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $work_experience = WorkExperience::find($id);

            if(!$work_experience)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json([
                'data' => new WorkExperienceResource($work_experience),
                'message' => 'Work experience record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, WorkExperienceRequest $request)
    {
        try{
            $success = [];

            foreach($request->work_experiences as $work_experience){
                $cleanData = [];

                foreach ($work_experience as $key => $value) {
                    if($key === 'id' && $value === null) continue;
                    if($value===null){
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = $value;
                }

                if($work_experience->id === null || $work_experience->id === 'id'){
                    $cleanData['personal_information_id'] = $id;
                    $work_experience = WorkExperience::create($cleanData);
                    if(!$work_experience) continue;
                    $success[] = $work_experience;
                    continue;
                }

                $work_experience = WorkExperience::find($work_experience->id);
                $work_experience->update($cleanData);

                $success[] = $work_experience;
            }

            return $success;
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            throw new \Exception("Failed to register employee work experience.", 400);
        }
    }
    
    public function updateSingleData($id, WorkExperienceRequest $request)
    {
        try{
            $cleanData = [];
            $work_experience = WorkExperience::find($id);

            if(!$work_experience){
                return response()->json(['message' => "No record found."], Response::HTTP_NOT_FOUND);
            }

            foreach ($request->all() as $key => $value) {
                if($value===null){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = $value;
            }
            
            $work_experience->update($cleanData);

            return response()->json([
                'data' => new WorkExperienceResource($work_experience),
                'message' => "Work experience updated successfully"
            ]);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            throw new \Exception("Failed to update employee work experience.", 400);
        }
    }

    public function updateMany(WorkExperienceManyRequest $request)
    {
        try{
            $cleanData  = [];
            $failed = [];
            $success = [];

            foreach($request->work_experiences as $work_experience){
                $cleanNewData = [];
                foreach($work_experience as $key => $fields){
                    if($fields === null || $fields === 'null'){
                        $cleanNewData[$key] = $fields;
                        continue;
                    }
                    $cleanNewData[$key] = strip_tags($fields);
                }
                $cleanData[] = $cleanNewData;
            }

            foreach ($cleanData as $key => $work_experience) {
                $work_experience_new = WorkExperience::find($work_experience->id);

                if(!$work_experience_new)
                {
                    $failed[] = $work_experience;
                    continue;
                }

                $work_experience_new->update($cleanData);
                $success[] = $work_experience_new;
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            if(count($cleanData) === count($failed)){
                return response()->json([
                    'message' => "Request to update work experience records has failed.",
                    'failed' => $failed
                ], Response::HTTP_BAD_REQUEST);
            }

            if(count($failed) > 0 && count($success) > count($failed)){
                return response()->json([
                    'data' => WorkExperienceResource::collection($success), 
                    'failed' => $failed,
                    'message' => "Successfully update some work experience record.",
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => WorkExperienceResource::collection($success),
                'message' => 'Employee work experience data is updated.'
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

            $work_experience = WorkExperience::findOrFail($id);

            if(!$work_experience)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $work_experience -> delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Work experience record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['meTruessage' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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

            $work_experience = WorkExperience::where('personal_information_id', $id)->get();

            if(!$work_experience || count($work_experience) === 0)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($work_experience as $key => $value){
                $value->delete();
            }
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee work experience record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroyByPersonalInformationID', $th->getMessage());
            return response()->json(['meTruessage' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByEmployeeProfileID($id, AuthPinApprovalRequest $request)
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
            $work_experience = $personal_information->workExperience;

            foreach($work_experience as $key => $value){
                $value->delete();
            }
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee work experience record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroyByPersonalInformationID', $th->getMessage());
            return response()->json(['meTruessage' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
