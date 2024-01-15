<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Services\FileValidationAndUpload;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\DivisionRequest;
use App\Http\Requests\DivisionAssignChiefRequest;
use App\Http\Requests\DivisionAssignOICRequest;
use App\Http\Resources\DivisionResource;
use App\Models\Division;
use App\Models\EmployeeProfile;

class DivisionController extends Controller
{
    private $CONTROLLER_NAME = 'Division';
    private $PLURAL_MODULE_NAME = 'divisions';
    private $SINGULAR_MODULE_NAME = 'division';

    protected $file_validation_and_upload;

    public function __construct(FileValidationAndUpload $file_validation_and_upload)
    {
        $this->file_validation_and_upload = $file_validation_and_upload;
    }

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $divisions = Cache::remember('divisions', $cacheExpiration, function(){
                return Division::all();
            });

            return response()->json([
                'data' => DivisionResource::collection($divisions), 
                'message' => 'Division record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign Chief or Head
     * This must be in division/department/section/unit
     */
    
    public function assignChiefByEmployeeID($id, DivisionAssignChiefRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->password);

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $division = Division::find($id);

            if(!$division)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }  

            $employee_profile = EmployeeProfile::where('employee_id', $request['employee_id'])->first();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];
            $cleanData['chief_employee_profile_id'] = $employee_profile->id;
            $cleanData['chief_attachment_url'] = $request->input('attachment')===null?'NONE': $this->file_validation_and_upload->check_save_file($request, 'division/files');
            $cleanData['chief_effective_at'] = Carbon::now();

            $division->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in assigning division chief '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => new DivisionResource($division), 
                'message' => 'New chief assigned in department.'], 
                Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'assignChiefByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign Officer in charge
     * This must be in division/department/section/unit
     * Validate first for rights to assigned OIC by password of chief/head/supervisor
     */
    public function assignOICByEmployeeID($id, DivisionAssignOICRequest $request)
    {
        try{
            $division = Division::find($id);

            if(!$division)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }  

            $employee_profile = EmployeeProfile::where('employee_id', $request['employee_id'])->first();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            } 

            if($employee_profile->id !== $division->chief_employee_profile_id){
                return response()->json(['message' => 'UnAuthorized.'], Response::HTTP_UNAUTHORIZED);
            }

            $user = $request->user;
            $cleanData['password'] = strip_tags($request->password);

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $cleanData = [];
            $cleanData['oic_employee_profile_id'] = $employee_profile->id;
            $cleanData['oic_attachment_url'] = $request->input('attachment')===null?'NONE': $this->file_validation_and_upload->check_save_file($request, 'division/files');
            $cleanData['oic_effective_at'] = strip_tags($request->input('effective_at'));
            $cleanData['oic_end_at'] = strip_tags($request->input('end_at'));

            $division->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in assigning chief '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => new DivisionResource($division),
                'message' => 'New officer incharge assign in department.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'assignOICByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(DivisionRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null)
                {
                    $cleanData[$key] = $value;
                    continue;
                }

                if($key === 'attachment')
                {
                    $cleanData['division_attachment_url'] = $this->file_validation_and_upload->check_save_file($request, 'division/files');
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $division = Division::create($cleanData);

            Helpers::registerSystemLogs($request, $division['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new DivisionResource($division),
                'message' => 'Division created successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $division = Division::findOrFail($id);

            if(!$division)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new DivisionResource($division),
                'message' => 'Division details found.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, DivisionRequest $request)
    {
        try{
            $division = Division::find($id);

            if(!$division)
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
                
                if($key === 'attachment')
                {
                    $cleanData['division_attachment_url'] = $this->file_validation_and_upload->check_save_file($request, 'division\files');
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }
            $division -> update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new DivisionResource($division),
                'message' => 'Division updated successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $division = Division::findOrFail($id);

            if(!$division)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            if(count($division->departments) > 0)
            {
                return response()->json(['message' => 'Some data is using this record deletion is prohibited.'], Response::HTTP_BAD_REQUEST);
            }

            $division -> delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Division deleted successfully.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
