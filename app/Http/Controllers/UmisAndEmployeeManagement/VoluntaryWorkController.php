<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\VoluntaryWorkManyRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\VoluntaryWorkRequest;
use App\Http\Resources\VoluntaryWorkResource;
use App\Models\VoluntaryWork;
use App\Models\EmployeeProfile;

class VoluntaryWorkController extends Controller
{
    private $CONTROLLER_NAME = 'VoluntaryWork';
    private $PLURAL_MODULE_NAME = 'voluntary_works';
    private $SINGULAR_MODULE_NAME = 'voluntary_work';

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $voluntary_works = VoluntaryWork::where('personal_information_id', $id)->get();

            if(!$voluntary_works)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => VoluntaryWorkResource::collection($voluntary_works), 
                'message' => 'Employee voluntary work record retrieved.'
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
            $voluntary_works = $personal_information->voluntary_works;

            return response()->json([
                'data' => VoluntaryWorkResource::collection($voluntary_works), 
                'message' => 'Employee voluntary work record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(VoluntaryWorkRequest $request)
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

            $voluntary_work = VoluntaryWork::create($cleanData);

            Helpers::registerSystemLogs($request, $voluntary_work['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new VoluntaryWorkResource($voluntary_work),
                'message' => 'New employee voluntary work registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function storeMany($personal_information_id, VoluntaryWorkRequest $request)
    {
        try{
            $success = [];

            foreach($request->voluntary_work as $voluntary_work){
                $cleanData = [];
                $cleanData['personal_information_id'] = $personal_information_id;
                foreach ($voluntary_work as $key => $value) {
                    if ($value === null) {
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($value);
                }
                $voluntary_work = VoluntaryWork::create($cleanData);

                if(!$voluntary_work){
                    continue;
                }

                $success[] = $voluntary_work;
            };

            return $success;
        }catch(\Throwable $th){
            throw new \Exception("Failed to register employee voluntary work.", 400);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $voluntary_work = VoluntaryWork::findOrFail($id);

            if(!$voluntary_work)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new VoluntaryWorkResource ($voluntary_work), 
                'message' => 'Voluntary work record found.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, VoluntaryWorkRequest $request)
    {
        try{
            $success = [];

            foreach($request->voluntary_work as $voluntary_work){
                $cleanData = [];
                foreach ($voluntary_work as $key => $value) {
                    if($key === 'id' && $value === null) continue;
                    if ($value === null) {
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($value);
                }

                if($voluntary_work->id === null || $voluntary_work->id === 'null'){
                    $cleanData['personal_information_id'] = $id;
                    $voluntary_work = VoluntaryWork::create($cleanData);
                    if(!$voluntary_work) continue;
                    $success[] = $voluntary_work;
                    continue;
                }

                $voluntary_work = VoluntaryWork::find($voluntary_work->id);
                $voluntary_work->update($cleanData);

                $success[] = $voluntary_work;
            };

            return $success;
        }catch(\Throwable $th){
            throw new \Exception("Failed to register employee voluntary work.", 400);
        }
    }

    public function updateMany(VoluntaryWorkManyRequest $request)
    {
        try{
            $cleanData  = [];
            $failed = [];
            $success = [];

            foreach($request->voluntary_work_experiences as $voluntary_work_experience){
                $cleanNewData = [];
                foreach($voluntary_work_experience as $key => $fields){
                    if($fields === null || $fields === 'null'){
                        $cleanNewData[$key] = $fields;
                        continue;
                    }
                    $cleanNewData[$key] = strip_tags($fields);
                }
                $cleanData[] = $cleanNewData;
            }

            foreach ($cleanData as $key => $voluntary_work_experience) {
                $voluntary_work_experience_new = VoluntaryWork::find($voluntary_work_experience->id);

                if(!$voluntary_work_experience_new)
                {
                    $failed[] = $voluntary_work_experience;
                    continue;
                }

                $voluntary_work_experience_new->update($cleanData);
                $success[] = $voluntary_work_experience_new;
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            if(count($cleanData) === count($failed)){
                return response()->json([
                    'message' => "Request to update voluntary work experience records has failed.",
                    'failed' => $failed
                ], Response::HTTP_BAD_REQUEST);
            }

            if(count($failed) > 0 && count($success) > count($failed)){
                return response()->json([
                    'data' => VoluntaryWorkResource::collection($success), 
                    'failed' => $failed,
                    'message' => "Successfully update some voluntary work experience record.",
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => VoluntaryWorkResource::collection($success),
                'message' => 'Employee voluntary work experience data is updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'updateMany', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            

            $voluntary_work = VoluntaryWork::findOrFail($id);

            if(!$voluntary_work)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $voluntary_work->delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee voluntary work record deleted.'], Response::HTTP_OK);
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

            $voluntary_works = VoluntaryWork::where('personal_information_id', $id)->get();

            if(!$voluntary_works)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($voluntary_works as $key => $voluntary_work){
                $voluntary_work->delete();
            }
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee voluntary works record deleted.'], Response::HTTP_OK);
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
            $voluntary_works = $personal_information->voluntary_works;

            foreach($voluntary_works as $key => $voluntary_work){
                $voluntary_work->delete();
            }
            
            Helpers::registerSystemLogs($request, null, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee voluntary works record deleted'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
