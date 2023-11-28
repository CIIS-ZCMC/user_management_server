<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use App\Services\RequestLogger;
use App\Services\FileValidationAndUpload;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\UnitRequest;
use App\Http\Requests\UnitAssignOICRequest;
use App\Http\Requests\UnitAssignHeadRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Models\EmployeeProfile;

class UnitController extends Controller
{  
    private $CONTROLLER_NAME = 'Unit';
    private $PLURAL_MODULE_NAME = 'units';
    private $SINGULAR_MODULE_NAME = 'unit';

    protected $requestLogger;
        protected $file_validation_and_upload;

    public function __construct(RequestLogger $requestLogger, FileValidationAndUpload $file_validation_and_upload)
    {
        $this->requestLogger = $requestLogger;
        $this->file_validation_and_upload = $file_validation_and_upload;
    }

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $units = Cache::remember('units', $cacheExpiration, function(){
                return Unit::all();
            });

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => UnitResource::collection($units),
                'message' => 'Unit list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Assign Supervisor
     * This must be in unit
     */
    public function assignHeadByEmployeeID($id, UnitAssignHeadRequest $request)
    {
        try{
            $unit = Unit::find($id);

            if(!$unit)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }  

            $employee_profile = EmployeeProfile::where('employee_id', $request['employee_id'])->first();
            $assigned_area = $employee_profile->assignedArea;
            $employee_designation = $assigned_area->plantilla_id === null?$assigned_area->designation:$assigned_area->plantilla->designation;

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            } 

            if(!$employee_designation['code'].include($unit['job_specification']))
            {
                return response()->json(['message' => 'Invalid job specification.'], Response::HTTP_BAD_REQUEST);
            }

            $cleanData = [];
            $cleanData['head_employee_profile_id'] = $employee_profile->id;
            $cleanData['head_attachment_url'] = $request->input('attachment')===null?'NONE': $this->file_validation_and_upload->check_save_file($request->input('attachment'), "unit/files");
            $cleanData['head_effective_at'] = Carbon::now();

            $unit->update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in assigning head '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => new UnitResource($unit), 'message' => 'New unit head assigned.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'assignHeadByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign Officer in charge
     * This must be in unit/department/unit/unit
     * Validate first for rights to assigned OIC by password of head/head/head
     */
    public function assignOICByEmployeeID($id, UnitAssignOICRequest $request)
    {
        try{
            $unit = Unit::find($id);

            if(!$unit)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }  

            $employee_profile = EmployeeProfile::where('employee_id', $request['employee_id'])->first();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            } 

            $user = $request->user;
            $cleanData['password'] = strip_tags($request->input('password'));

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $cleanData = [];
            $cleanData['oic_employee_profile_id'] = $employee_profile->id;
            $cleanData['oic_attachment_url'] = $request->input('attachment')===null?'NONE': $this->file_validation_and_upload->check_save_file($request->input('attachment'), "unit/files");
            $cleanData['oic_effective_at'] = strip_tags($request->input('effective_at'));
            $cleanData['oic_end_at'] = strip_tags($request->input('end_at'));

            $unit->update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in assigning officer in charge '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => new UnitResource($unit),
                'message' => 'Officer incharge assigned to unit'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'assignOICByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(UnitRequest $request)
    {
        try{
            $cleanData = [];

            $section = Section::find($request->input('section_id'));

            if(!$section){
                return response()->json(['message' => 'Section is required.'], Response::HTTP_BAD_REQUEST);
            }
            
            foreach ($request->all() as $key => $value) {
                if($value === null)
                {
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'attachment')
                {
                    $cleanData['unit_attachment_url'] = $this->file_validation_and_upload->check_save_file($request,'unit/files');
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $unit = Unit::create($cleanData);

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new UnitResource($unit),
                'message' => 'New unit added.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $unit = Unit::find($id);

            if(!$unit)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new UnitResource($unit), 'message' => 'Unit record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, UnitRequest $request)
    {
        try{
            $unit = Unit::find($id);

            if(!$unit)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $section = Section::find($request->input('section_id'));

            if(!$section){
                return response()->json(['message' => 'Section is required.'], Response::HTTP_BAD_REQUEST);
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
                    $cleanData['unit_attachment_url'] = $this->file_validation_and_upload->check_save_file($request,'unit/files');
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $unit -> update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new UnitResource($unit),
                'message' => 'Updated unit details.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->input('password'));

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $unit = Unit::findOrFail($id);

            if(!$unit)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $unit -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Unit record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
