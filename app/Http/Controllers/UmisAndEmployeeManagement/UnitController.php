<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Models\Section;
use App\Models\SpecialAccessRole;
use App\Models\SystemRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use App\Helpers\Helpers;
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

    protected $file_validation_and_upload;

    public function __construct(FileValidationAndUpload $file_validation_and_upload)
    {
        $this->file_validation_and_upload = $file_validation_and_upload;
    }

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $units = Cache::remember('units', $cacheExpiration, function(){
                return Unit::all();
            });

            return response()->json([
                'data' => UnitResource::collection($units),
                'message' => 'Unit list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
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
            $user = $request->user;
            $previous_head = null;
            $system_role = null;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

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

            if($unit->head_employee_profile_id !== null){
                $previous_head = $unit->head_employee_profile_id;
            }

            $cleanData = [];
            $cleanData['head_employee_profile_id'] = $employee_profile->id;
            $cleanData['head_attachment_url'] = $request->attachment===null?'NONE': $this->file_validation_and_upload->check_save_file($request, "unit/files");
            $cleanData['head_effective_at'] = Carbon::now();

            $unit->update($cleanData);
            
            $system_role = SystemRole::where('code', 'UNIT-HEAD-01')->first();

            SpecialAccessRole::create([
                'system_role_id' => $system_role->id,
                'employee_profile_id' => $employee_profile->id
            ]);

            /**
             * Revoke Previous Head rights as Division Head
             */
            if($previous_head !== null){
                $access_right = SpecialAccessRole::where('employee_profile_id', $previous_head)->where('system_role_id', $system_role->id)->first();
                $access_right->delete();
            }

            Helpers::notifications($employee_profile->id, "You been assigned as unit head of ".$unit->name." unit.");
            Helpers::registerSystemLogs($request, $id, true, 'Success in assigning head '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => new UnitResource($unit), 
                'message' => 'New unit head assigned.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'assignHeadByEmployeeID', $th->getMessage());
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
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

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

            $cleanData = [];
            $cleanData['oic_employee_profile_id'] = $employee_profile->id;
            $cleanData['oic_attachment_url'] = $request->attachment===null?'NONE': $this->file_validation_and_upload->check_save_file($request, "unit/files");
            $cleanData['oic_effective_at'] = strip_tags($request->effective_at);
            $cleanData['oic_end_at'] = strip_tags($request->end_at);

            $unit->update($cleanData);

            Helpers::notifications($employee_profile->id, "You been assigned as officer in charge of ".$unit->name." unit.");
            Helpers::registerSystemLogs($request, $id, true, 'Success in assigning officer in charge '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => new UnitResource($unit),
                'message' => 'Officer incharge assigned to unit'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'assignOICByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(UnitRequest $request)
    {
        try{
            $cleanData = [];

            $section = Section::find($request->section_id);

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

            $check_if_exist =  Unit::where('name', $cleanData['name'])->where('code', $cleanData['code'])->first();

            if($check_if_exist !== null){
                return response()->json(['message' => 'Unit already exist.'], Response::HTTP_FORBIDDEN);
            }

            $unit = Unit::create($cleanData);

            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new UnitResource($unit),
                'message' => 'Unit created successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
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

            return response()->json(['data' => new UnitResource($unit), 'message' => 'Unit record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, UnitRequest $request)
    {

        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $unit = Unit::find($id);

            if(!$unit)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $section = Section::find($request->section_id);

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

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new UnitResource($unit),
                'message' => 'Unit updated successfully'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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

            $unit = Unit::findOrFail($id);

            if(!$unit)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $unit -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Unit deleted successfully.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
