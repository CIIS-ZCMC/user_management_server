<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\ChildManyRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\ChildFullResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\ChildRequest;
use App\Http\Resources\ChildResource;
use App\Models\Child;
use App\Models\EmployeeProfile;
use App\Models\PersonalInformation;

class ChildController extends Controller
{
    private $CONTROLLER_NAME = 'Child';
    private $PLURAL_MODULE_NAME = 'children';
    private $SINGULAR_MODULE_NAME = 'child';

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $children = Child::where('personal_information_id', $id)->get();

            if(!$children)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => ChildResource::collection($children),
                'message' => 'Employee children record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID(Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::where('employee_id',$request->employee_id)->get();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $personal_information = $employee_profile->personalInformation;
            $children = $personal_information->children;

            return response()->json([
                'data' => ChildResource::collection($children),
                'message' => 'Employee children record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(ChildRequest $request)
    {
        try{
            $cleanData = [];

            $personal_information = PersonalInformation::find($request->personal_information_id);

            if(!$personal_information)
            {
                return response()->json(['message'=> 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $child = Child::create($cleanData);

            Helpers::registerSystemLogs($request, $child['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new ChildResource($child),
                'message' => 'New employee child record added.'
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

            $personal_information = PersonalInformation::find($request->input('personal_information_id'));

            if(!$personal_information)
            {
                return response()->json(['message'=> 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($request->children as $child){
                $cleanData = [];
                foreach ($child as $key => $value) {
                    if ($value === null) {
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($value);
                }
                $child = Child::create($cleanData);

                if(!$child){
                    $failed[] = $cleanData;
                    continue;
                }

                $success = $child;
            }

            Helpers::registerSystemLogs($request, $child['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            if(count($failed) > 0){
                return response()->json([
                    'data' => ChildResource::collection($success),
                    'failed' => $failed,
                    'message' => 'Some data failed to registere.'
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => ChildResource::collection($success),
                'message' => 'New employee child record added.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $child = Child::findOrFail($id);

            if(!$child)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new ChildFullResource($child),
                'message' => 'Employee child record retrieved'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, ChildRequest $request)
    {
        try{
            $child = Child::find($id);

            if(!$child)
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

            $child->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new ChildResource($child), 
                'message' => 'Employee child data is updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function updateMany(ChildManyRequest $request)
    {
        try{
            $cleanData  = [];
            $failed = [];
            $success = [];

            foreach($request->childrens as $child){
                $cleanNewData = [];
                foreach($child as $key => $fields){
                    if($fields === null || $fields === 'null'){
                        $cleanNewData[$key] = $fields;
                        continue;
                    }
                    $cleanNewData[$key] = strip_tags($fields);
                }
                $cleanData[] = $cleanNewData;
            }

            foreach ($cleanData as $key => $child) {
                $child_new = Child::find($child->id);

                if(!$child_new)
                {
                    $failed[] = $child_new;
                    continue;
                }

                $child->update($cleanData);
                $success[] = $child_new;
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            if(count($cleanData) === count($failed)){
                return response()->json([
                    'message' => "Request to update children records has failed.",
                    'failed' => $failed
                ], Response::HTTP_BAD_REQUEST);
            }

            if(count($failed) > 0 && count($success) > count($failed)){
                return response()->json([
                    'data' => ChildResource::collection($success), 
                    'failed' => $failed,
                    'message' => "Successfully update some children record.",
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => ChildResource::collection($success),
                'message' => 'Employee children data is updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'updateMany', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            // $password = strip_tags($request->password);

            // $employee_profile = $request->user;

            // $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            // if (!Hash::check($password.Cache::get('salt_value'), $password_decrypted)) {
            //     return response()->json(['message' => "Password incorrect."], Response::HTTP_FORBIDDEN);
            // }

            $child = Child::findOrFail($id);

            if(!$child)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $child->delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee child record deleted.'], Response::HTTP_OK);
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

            $children = Child::where('personal_information_id', $id)->get();

            if(count($children) === 0)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($children as $key => $child){
                $child->delete();
            }
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee children record deleted.'], Response::HTTP_OK);
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
            $children = $personal_information->children;

            if(count($children) === 0)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($children as $key => $child){
                $child->delete();
            }
            
            Helpers::registerSystemLogs($request, null, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee children record deleted'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
