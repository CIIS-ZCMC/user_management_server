<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\PasswordApprovalRequest;
use App\Models\Department;
use App\Models\Division;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Services\RequestLogger;
use App\Services\FileValidationAndUpload;
use App\Http\Requests\SectionRequest;
use App\Http\Requests\SectionAssignSupervisorRequest;
use App\Http\Requests\SectionAssignOICRequest;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use App\Models\EmployeeProfile;

class SectionController extends Controller
{    
    private $CONTROLLER_NAME = 'Section';
    private $PLURAL_MODULE_NAME = 'sections';
    private $SINGULAR_MODULE_NAME = 'section';

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

            $sections = Cache::remember('sections', $cacheExpiration, function(){
                return Section::all();
            });

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => SectionResource::collection($sections),
                'message' => 'Section list retrieved'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Assign Supervisor
     * This must be in section
     */
    public function assignSupervisorByEmployeeID($id, SectionAssignSupervisorRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $section = Section::find($id);

            if(!$section)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }  

            $employee_profile = EmployeeProfile::where('employee_id', $request['employee_id'])->first();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            } 

            $cleanData = [];
            $cleanData['supervisor_employee_profile_id'] = $employee_profile->id;
            $cleanData['supervisor_attachment_url'] = $request->attachment===null?'NONE': $this->file_validation_and_upload->check_save_file($request->input('attachment'),"section/files");
            $cleanData['supervisor_effective_at'] = Carbon::now();

            $section->update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in assigning supervisor '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => new SectionResource($section),
                'message' => 'Section supervisor registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'assignSupervisorByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign Officer in charge
     * This must be in section/department/section/unit
     * Validate first for rights to assigned OIC by password of supervisor/head/supervisor
     */
    public function assignOICByEmployeeID($id, SectionAssignOICRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $section = Section::find($id);

            if(!$section)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }  

            $employee_profile = EmployeeProfile::where('employee_id', $request['employee_id'])->first();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            } 

            $user = $request->user;
            $cleanData['password'] = strip_tags($request->password);

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $cleanData = [];
            $cleanData['oic_employee_profile_id'] = $employee_profile->id;
            $cleanData['oic_attachment_url'] = $request->input('attachment')===null?'NONE': $this->file_validation_and_upload->check_save_file($request->input('attachment'),"section/files");
            $cleanData['oic_effective_at'] = strip_tags($request->effective_at);
            $cleanData['oic_end_at'] = strip_tags($request->end_at);

            $section->update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in assigning officer in charge '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => new SectionResource($section),
                'message' => 'Section officer incharge registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'assignOICByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(SectionRequest $request)
    {
        try{
            $cleanData = [];

            /**
             * Validate if no given diviosn id or department id
             * as it is important and required by the system.
             */
            if($request->division_id === null && $request->department_id === null)
            {
                return response() -> json(['message'=> 'Division or Department area is required.'], Response::HTTP_BAD_REQUEST);
            }

            /**
             * Validate if has division id
             * Validate if division id trully exist.
             */
            if($request->division_id !== null)
            {
                $division = Division::find($request->division_id);

                if(!$division)
                {
                    return response()->json(['message' => 'No division record found for id '.$request->input('division_id')], Response::HTTP_BAD_REQUEST);
                }
            }

            /**
             * Validate if has department id
             * Validate if department id trully exist.
             */
            if($request->department_id !== null)
            {
                $division = Department::find($request->department_id);

                if(!$division)
                {
                    return response()->json(['message' => 'No department record found for id '.$request->department_id], Response::HTTP_BAD_REQUEST);
                }
            }
            
            foreach ($request->all() as $key => $value) {
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'attachment'){
                    $cleanData['section_attachment_url'] = $this->file_validation_and_upload->check_save_file($request, 'section/files');
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $section = Section::create($cleanData);

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new SectionResource($section),
                'message' => 'Section created successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $section = Section::find($id);

            if(!$section)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new SectionResource($section), 'message' => 
                'Section record found.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, SectionRequest $request)
    {
        try{
            $section = Section::find($id);

            if(!$section)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $cleanData = [];

            /**
             * Validate if no given diviosn id or department id
             * as it is important and required by the system.
             */
            if($request->input('division_id') === null && $request->input('department_id') === null)
            {
                return response() -> json(['message'=> 'Division or Department area is required.'], Response::HTTP_BAD_REQUEST);
            }

            /**
             * Validate if has division id
             * Validate if division id trully exist.
             */
            if($request->input('division_id') !== null)
            {
                $division = Division::find($request->input('division_id'));

                if(!$division)
                {
                    return response()->json(['message' => 'No division record found for id '.$request->input('division_id')], Response::HTTP_BAD_REQUEST);
                }
            }

            /**
             * Validate if has department id
             * Validate if department id trully exist.
             */
            if($request->input('department_id') !== null)
            {
                $division = Department::find($request->input('department_id'));

                if(!$division)
                {
                    return response()->json(['message' => 'No department record found for id '.$request->input('department_id')], Response::HTTP_BAD_REQUEST);
                }
            }
            
            foreach ($request->all() as $key => $value) {
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'attachment'){
                    $cleanData['section_attachment_url'] = $this->file_validation_and_upload->check_save_file($request, 'section/files');
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $section -> update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new SectionResource($section),
                'message' => 'Section updated successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
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

            $section = Section::findOrFail($id);

            if(!$section)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $section -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Section deleted successfully.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
