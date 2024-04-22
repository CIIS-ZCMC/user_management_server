<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\TrainingManyRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\TrainingRequest;
use App\Http\Resources\TrainingResource;
use App\Models\Training;
use App\Models\EmployeeProfile;

class TrainingController extends Controller
{
    private $CONTROLLER_NAME = 'Training';
    private $PLURAL_MODULE_NAME = 'trainings';
    private $SINGULAR_MODULE_NAME = 'training';

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $training = Training::where('personal_information_id',$id)->get();

            if(!$training)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json([
                'data' => new TrainingResource($training),
                'message' => 'Employee Training record retrieved'
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

            $personal_information = $employee_profile->personalInformation;
            $training = $personal_information->training;
            
            return response()->json([
                'data' => new TrainingResource($training),
                'message' => 'Employee Training record retrieved'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(TrainingRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'type_is_lnd'){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $training = Training::create($cleanData);

            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new TrainingResource($training),
                'message' => 'New Learning and Development (L&D) record added.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function employeeUpdateTraining(Request $request)
    {
        try{
            $personal_information = $request->user->personalInformation;
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'type_is_lnd'){
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'attachment'){
                    $attachment = Helpers::checkSaveFile($request->attachment, '/training');
                    $cleanData['attachment'] = $attachment;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $cleanData['personal_information_id'] = $personal_information->id;
            $cleanData['is_request'] = true;
            $training = Training::create($cleanData);

            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new TrainingResource($training),
                'message' => 'New Learning and Development (L&D) record added.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function storeMany($personal_information_id, TrainingManyRequest $request)
    {
        try{
            $success = [];

            foreach($request->trainings as $training){
                $cleanData = [];
                $cleanData['personal_information_id'] = $personal_information_id;
                foreach ($training as $key => $value) {
                    if($value === null || $key === 'type_is_lnd'){
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($value);
                }

                $training = Training::create($cleanData);

                if(!$training){
                    $failed[] = $cleanData;
                    continue;
                }

                $success[] = $training;
            }
            
            return $success;
        }catch(\Throwable $th){
            throw new \Exception("Failed to register employee training record.", 400);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $training = Training::find($id);

            if(!$training)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json([
                'data' => new TrainingResource($training),
                'message' => 'Training record retrived.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, TrainingRequest $request)
    {
        try{
            $success = [];

            foreach($request->trainings as $training){
                $cleanData = [];
                foreach ($training as $key => $value) {
                    if($key === 'id' && $value === null) continue;
                    if($value === null || $key === 'type_is_lnd'){
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($value);
                }

                if($training->id === null || $training->id === 'null'){
                    $cleanData['personal_information_id'] = $id;
                    $training = Training::create($cleanData);
                    if(!$training) continue;
                    $success[] = $training;
                    continue;
                }
                
                $training = Training::find($id);
                $training->update($cleanData);
                $success[] = $training;
            }
            
            return $success;
        }catch(\Throwable $th){
            throw new \Exception("Failed to register employee training record.", 400);
        }
    }

    public function updateMany(TrainingManyRequest $request)
    {
        try{
            $cleanData  = [];
            $failed = [];
            $success = [];

            foreach($request->trainings as $training){
                $cleanNewData = [];
                foreach($training as $key => $fields){
                    if($fields === null || $fields === 'null'){
                        $cleanNewData[$key] = $fields;
                        continue;
                    }
                    $cleanNewData[$key] = strip_tags($fields);
                }
                $cleanData[] = $cleanNewData;
            }

            foreach ($cleanData as $key => $training) {
                $training_new = Training::find($training->id);

                if(!$training_new)
                {
                    $failed[] = $training;
                    continue;
                }

                $training_new->update($cleanData);
                $success[] = $training_new;
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            if(count($cleanData) === count($failed)){
                return response()->json([
                    'message' => "Request to update training records has failed.",
                    'failed' => $failed
                ], Response::HTTP_BAD_REQUEST);
            }

            if(count($failed) > 0 && count($success) > count($failed)){
                return response()->json([
                    'data' => TrainingResource::collection($success), 
                    'failed' => $failed,
                    'message' => "Successfully update some training record.",
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => TrainingResource::collection($success),
                'message' => 'Employee training data is updated.'
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

            $training = Training::findOrFail($id);

            if(!$training)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $training -> delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Learning and Development (L&D) record deleted'], Response::HTTP_OK);
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

            $training = Training::where('personal_information_id', $id)->get();

            if(!$training || count($training))
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($training as $key => $value)
            {
                $value -> delete();
            }
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Success'], Response::HTTP_OK);
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

            $personal_information = $employee_profile->personalInformation;
            $training = $personal_information->training;

            foreach($training as $key => $value)
            {
                $value -> delete();
            }
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroyByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
