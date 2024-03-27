<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Models\SpecialAccessRole;
use App\Models\SystemRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use App\Helpers\Helpers;
use App\Services\FileValidationAndUpload;
use App\Http\Requests\DepartmentRequest;
use App\Http\Requests\DepartmentAssignHeadRequest;
use App\Http\Requests\DepartmentAssignTrainingOfficerRequest;
use App\Http\Requests\DepartmentAssignOICRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Division;
use App\Models\Department;
use App\Models\EmployeeProfile;

class DepartmentController extends Controller
{
    private $CONTROLLER_NAME = 'Department';
    private $PLURAL_MODULE_NAME = 'departments';
    private $SINGULAR_MODULE_NAME = 'department';
    protected $file_validation_and_upload;

    public function __construct(FileValidationAndUpload $file_validation_and_upload)
    {
        $this->file_validation_and_upload = $file_validation_and_upload;
    }

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $departments = Cache::remember('departments', $cacheExpiration, function(){
                return Department::all();
            });

            return response()->json([
                'data' => DepartmentResource::collection($departments),
                'message' => 'Department records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Assign Head
     * This must be in department/department/section/unit
     */
    public function assignHeadByEmployeeID($id, DepartmentAssignHeadRequest $request)
    {
        try{
            $user = $request->user;
            $previous_head = null;
            $system_role = null;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $department = Department::find($id);

            if(!$department)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }  

            $employee_profile = EmployeeProfile::where('employee_id', $request['employee_id'])->first();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            } 

            if($department->head_employee_profile_id !== null){
                $previous_head = $department->head_employee_profile_id;
            }

            $cleanData = [];
            $cleanData['head_employee_profile_id'] = $employee_profile->id;
            $cleanData['head_attachment_url'] = $request->input('attachment')===null?'NONE': $this->file_validation_and_upload->check_save_file($request, 'department/files');
            $cleanData['head_effective_at'] = Carbon::now();

            $department->update($cleanData);

            $system_role = SystemRole::where('code', 'DEPT-HEAD-04')->first();

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

            Helpers::registerSystemLogs($request, $id, true, 'Success in assigning head'.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => new DepartmentResource($department), 
                'message' => 'New department head assigned.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'assignHeadByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
    /**
     * Assign Training officer
     * This must be in department
     */
    public function assignTrainingOfficerByEmployeeID($id, DepartmentAssignTrainingOfficerRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $department = Department::find($id);

            if(!$department)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }  

            $employee_profile = EmployeeProfile::where('employee_id', $request['employee_id'])->first();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            } 

            $cleanData = [];
            $cleanData['training_officer_employee_profile_id'] = $employee_profile->id;
            $cleanData['training_officer_attachment_url'] = $request->input('attachment')===null?'NONE':  $this->file_validation_and_upload->check_save_file($request, 'department/files');
            $cleanData['training_officer_effective_at'] = Carbon::now();

            $department->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in assigning head'.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => new DepartmentResource($department), 
                'message' => 'New training officer assigned.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'assignHeadByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign Officer in charge
     * This must be in department/department/section/unit
     * Validate first for rights to assigned OIC by password of chief/head/supervisor
     */
    public function assignOICByEmployeeID($id, DepartmentAssignOICRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $department = Department::find($id);

            if(!$department)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }  

            $employee_profile = EmployeeProfile::where('employee_id', $request['employee_id'])->first();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            } 

            if($employee_profile->id !== $department->head_employee_profile_id){
                return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
            }

            $cleanData = [];
            $cleanData['oic_employee_profile_id'] = $employee_profile->id;
            $cleanData['oic_attachment_url'] = $request->input('attachment')===null?'NONE': $this->file_validation_and_upload->check_save_file($request,'department/files');
            $cleanData['oic_effective_at'] = strip_tags($request->input('effective_at'));
            $cleanData['oic_end_at'] = strip_tags($request->input('end_at'));

            $department->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in assigning officer in charge '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => new DepartmentResource($department), 
                'message' => 'New officer incharge assigned.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'assignOICByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(DepartmentRequest $request)
    {
        try{
            $division_id = $request->division_id;
            $division = Division::find($division_id);

            if(!$division)
            {
                return response()->json(['message' => 'No division record found for id '.$division_id], Response::HTTP_BAD_REQUEST);
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
                    $cleanData['department_attachment_url'] = $this->file_validation_and_upload->check_save_file($request, 'department/files');
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $check_if_exist =  Department::where('name', $cleanData['name'])->where('code', $cleanData['code'])->first();

            if($check_if_exist !== null){
                return response()->json(['message' => 'Department already exist.'], Response::HTTP_FORBIDDEN);
            }

            $department = Department::create($cleanData);

            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new DepartmentResource($department),
                'message' => 'Department created successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $department = Department::find($id);

            if(!$department)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new DepartmentResource($department), 
                'message' => 'Department record found.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, DepartmentRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }
            
            $department = Department::find($id);

            if($department['division_id'] !== $request->input('division_id'))
            {
                $division = Division::find($request->input('division_id'));

                if(!$division)
                {
                    return response()->json([
                        'message' => 'No division record found with id of '.$request->input('division_id')
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            if(!$department)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null && $key === 'attachment')
                {
                    $cleanData['department_attachment_url'] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $department -> update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new DepartmentResource($department),
                'message' => 'Department updated successfully.'
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

            $department = Department::findOrFail($id);

            if(!$department)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $department -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Department deleted successfully.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
