<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\SectionRequest;
use App\Http\Requests\SectionAssignSupervisorRequest;
use App\Http\Requests\SectionAssignOICRequest;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use App\Models\EmployeeProfile;
use App\Models\SystemLogs;

class SectionController extends Controller
{    
    private $CONTROLLER_NAME = 'Section';
    private $PLURAL_MODULE_NAME = 'sections';
    private $SINGULAR_MODULE_NAME = 'section';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $sections = Cache::remember('sections', $cacheExpiration, function(){
                return Section::all();
            });

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

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
    public function assignSupervisorByEmployeeID($id, SectionAssignChiefRequest $request)
    {
        try{
            $section = Section::find($id);

            if(!$section)
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

            if(!$employee_designation['code'].include($section['job_specification']))
            {
                return response()->json(['message' => 'Invalid job specification.'], Response::HTTP_BAD_REQUEST);
            }

            $cleanData = [];
            $cleanData['supervisor_employee_profile_id'] = $employee_profile->id;
            $cleanData['supervisor_attachment_url'] = $request->input('attachment')===null?'NONE': $this->check_save_file($request->input('attachment'));
            $cleanData['supervisor_effective_at'] = Carbon::now();

            $section->update($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in assigning supervisor '.$this->PLURAL_MODULE_NAME.'.');

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
            $cleanData['password'] = strip_tags($request->input('password'));

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $cleanData = [];
            $cleanData['oic_employee_profile_id'] = $employee_profile->id;
            $cleanData['oic_attachment_url'] = $request->input('attachment')===null?'NONE': $this->check_save_file($request->input('attachment'));
            $cleanData['oic_effective_at'] = strip_tags($request->input('effective_at'));
            $cleanData['oic_end_at'] = strip_tags($request->input('end_at'));

            $section->update($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in assigning officer in charge '.$this->PLURAL_MODULE_NAME.'.');

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
            
            foreach ($request->all() as $key => $value) {
                if($value === null && $key === 'attachment')
                {
                    $cleanData['section_attachment_url'] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $section = Section::create($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new SectionResource($section),
                'message' => 'New section added.'
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

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

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

            foreach ($request->all() as $key => $value) {
                if($value === null && $key === 'attachment')
                {
                    $cleanData['section_attachment_url'] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $section -> update($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new SectionResource($section),
                'message' => 'Updated section details.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $section = Section::findOrFail($id);

            if(!$section)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $section -> delete();

            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Section record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function check_save_file($request)
    {
        $FILE_URL = 'employee/profiles';
        $fileName = '';

        if ($request->file('profile_image')->isValid()) {
            $file = $request->file('profile_image');
            $filePath = $file->getRealPath();

            $finfo = new \finfo(FILEINFO_MIME);
            $mime = $finfo->file($filePath);
            
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

            if (!in_array($mime, $allowedMimeTypes)) {
                return response()->json(['message' => 'Invalid file type'], 400);
            }

            // Check for potential malicious content
            $fileContent = file_get_contents($filePath);

            if (preg_match('/<\s*script|eval|javascript|vbscript|onload|onerror/i', $fileContent)) {
                return response()->json(['message' => 'File contains potential malicious content'], 400);
            }

            $file = $request->file('profile_image');
            $fileName = Hash::make(time()) . '.' . $file->getClientOriginalExtension();

            $file->move(public_path($FILE_URL), $fileName);
        }
        
        return $fileName;
    }
    
    protected function registerSystemLogs($request, $moduleID, $status, $remarks)
    {
        $ip = $request->ip();
        $user = $request->user;
        $permission = $request->permission;
        list($action, $module) = explode(' ', $permission);

        SystemLogs::create([
            'employee_profile_id' => $user->id,
            'module_id' => $moduleID,
            'action' => $action,
            'module' => $module,
            'status' => $status,
            'remarks' => $remarks,
            'ip_address' => $ip
        ]);
    }
}
