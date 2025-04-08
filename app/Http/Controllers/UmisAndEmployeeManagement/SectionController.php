<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\SectionsResource;
use App\Models\Department;
use App\Models\Division;
use App\Models\Notifications;
use App\Models\Role;
use App\Models\SpecialAccessRole;
use App\Models\SystemRole;
use App\Models\UserNotifications;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Helpers\Helpers;
use App\Services\FileValidationAndUpload;
use App\Http\Requests\SectionRequest;
use App\Http\Requests\SectionAssignSupervisorRequest;
use App\Http\Requests\SectionAssignOICRequest;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use App\Models\EmployeeProfile;
use Symfony\Component\HttpFoundation\JsonResponse;

class SectionController extends Controller
{    
    private $CONTROLLER_NAME = 'Section';
    private $PLURAL_MODULE_NAME = 'sections';
    private $SINGULAR_MODULE_NAME = 'section';

    protected $file_validation_and_upload;

    public function __construct(FileValidationAndUpload $file_validation_and_upload)
    {
        $this->file_validation_and_upload = $file_validation_and_upload;
    }

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $sections = Cache::remember('sections', $cacheExpiration, function(){
                return Section::all();
            });

            return response()->json([
                'data' => SectionResource::collection($sections),
                'message' => 'Section list retrieved'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
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
            $user = $request->user;
            $previous_head = null;
            $system_role = null;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
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

            if($section->supervisor_employee_profile_id !== null){
                $previous_head = $section->supervisor_employee_profile_id;
            }

            $cleanData = [];
            $cleanData['supervisor_employee_profile_id'] = $employee_profile->id;
            $cleanData['supervisor_attachment_url'] = $request->attachment===null?'NONE': Helpers::checkSaveFile($request->attachment,'section/files');
            $cleanData['supervisor_effective_at'] = Carbon::now();

            $section->update($cleanData);
            
            if($section->area_id === 'HOPPS-HRMO-DE-001'){
                $role = Role::where('code', 'HRMO-HEAD-01')->first();
                $system_role = SystemRole::where('role_id', $role->id)->first();

                SpecialAccessRole::create([
                    'system_role_id' => $system_role->id,
                    'employee_profile_id' => $employee_profile->id
                ]);
            }else{
                $role = Role::where('code', 'SECTION-HEAD-01')->first();
                $system_role = SystemRole::where('role_id', $role->id)->first();

                SpecialAccessRole::create([
                    'system_role_id' => $system_role->id,
                    'employee_profile_id' => $employee_profile->id
                ]);
            }

            /**
             * Revoke Previous Head rights as Division Head
             */
            if($previous_head !== null){
                $access_right = SpecialAccessRole::where('employee_profile_id', $previous_head)->where('system_role_id', $system_role->id)->first();
                $access_right->delete();
            }

            
            $title = "Congratulations!";
            $description = "You have been assigned as supervisor of " . $section->name;
            
            
            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/employees-per-area',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $employee_profile->id
            ]);

            Helpers::sendNotification([
                "id" => $employee_profile->employee_id, // EMPLOYEE_ID eg. 2023010250
                "data" => new NotificationResource($user_notification)
            ]);


            // Helpers::notifications($employee_profile->id, "You been assigned as section head of ".$section->name." section.");
            Helpers::registerSystemLogs($request, $id, true, 'Success in assigning supervisor '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => new SectionResource($section),
                'message' => 'Section supervisor registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'assignSupervisorByEmployeeID', $th->getMessage());
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
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
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

            if($employee_profile->id !== $section->supervisor_employee_profile_id){
                return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }

            $user = $request->user;
            $cleanData['password'] = strip_tags($request->password);

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'].config('app.salt_value'), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_FORBIDDEN);
            }

            $cleanData = [];
            $cleanData['oic_employee_profile_id'] = $employee_profile->id;
            $cleanData['oic_attachment_url'] = $request->input('attachment')===null?'NONE': Helpers::checkSaveFile($request->attachment,"section/files");
            $cleanData['oic_effective_at'] = strip_tags($request->effective_at);
            $cleanData['oic_end_at'] = strip_tags($request->end_at);

            $section->update($cleanData);

            Helpers::notifications($employee_profile->id, "You been assigned as officer in charge of ".$section->name." division.");
            Helpers::registerSystemLogs($request, $id, true, 'Success in assigning officer in charge '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => new SectionResource($section),
                'message' => 'Section officer incharge registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'assignOICByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(SectionRequest $request)
    {
        try{
            $cleanData = [];

            if($request->division_id === 'null' && $request->department_id === 'null'){
                return response()->json(['message' => "Section must be under a division or department."], Response::HTTP_BAD_REQUEST);
            }
            
            foreach ($request->all() as $key => $value) {
                if($key === 'department_id' || $key === 'division_id'){
                    if($value === 'null'){
                        $cleanData[$key] = null;
                        continue;
                    }
                    $cleanData[$key] = (int) $value;
                }
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'attachment'){
                    $cleanData['section_attachment_url'] = Helpers::checkSaveFile($request->attachment, 'section/files');
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $check_if_exist =  Section::where('name', $cleanData['name'])->where('code', $cleanData['code'])->first();

            if($check_if_exist !== null){
                return response()->json(['message' => 'Section already exist.'], Response::HTTP_FORBIDDEN);
            }

            $section = Section::create($cleanData);

            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new SectionResource($section),
                'message' => 'Section created successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
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

            return response()->json([
                'data' => new SectionResource($section), 'message' => 
                'Section record found.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, SectionRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password); 
            $division_id = null;
            $department_id = null;


             if($request->division_id === 'null' && $request->department_id === 'null'){
                return response()->json(['message' => "Section must be under a division or department."], Response::HTTP_BAD_REQUEST);
            }

            // if(($request->department_id === 'null' || $request->department_id === null) && $request->department_id === 'null' || $request->department_id === null){
            //     return response()->json(['message' => "Section must be under a division or department."], Response::HTTP_BAD_REQUEST);
            // }

            // if($request->department_id !== 'null' || $request->department_id !== null){
            //     if(!is_integer(strip_tags($request->department_id))){
            //         return response()->json(['message' => "Invalid data for department_id"], Response::HTTP_BAD_REQUEST);
            //     }
            //     $department_id = strip_tags($request->department_id);
            //     $department = Department::find($department_id);

            //     if(!$department){
            //         return response()->json(['message' => 'No department record found for id '.$department_id], Response::HTTP_BAD_REQUEST);
            //     }
            // }else{
            //     if(!is_integer(strip_tags($request->division_id))){
            //         return response()->json(['message' => "Invalid data for division_id"], Response::HTTP_BAD_REQUEST);
            //     }
            //     $division_id = strip_tags($request->division_id);
            //     $division = Division::find($division_id);

            //     if(!$division){
            //         return response()->json(['message' => 'No division record found for id '.$division_id], Response::HTTP_BAD_REQUEST);
            //     }
            // }

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }
            
            $section = Section::find($id);

            if(!$section)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $cleanData = [];
            // $cleanData['department_id'] = $department_id;
            // $cleanData['division_id'] = $division_id;

           
            
            foreach ($request->all() as $key => $value) {
                if($key === 'department_id' || $key === 'division_id'){
                    if($value === 'null'){
                        $cleanData[$key] = null;
                        continue;
                    }
                    $cleanData[$key] = (int) $value;
                }
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'attachment'){
                    $cleanData['section_attachment_url'] = Helpers::checkSaveFile($request->attachment, 'section/files');
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            
            // foreach ($request->all() as $key => $value) {
            //     if($value === null){
            //         $cleanData[$key] = $value;
            //         continue;
            //     }
            //     if($key === 'attachment'){
            //         $cleanData['section_attachment_url'] = $this->file_validation_and_upload->check_save_file($request, 'section/files');
            //         continue;
            //     }
            //     $cleanData[$key] = strip_tags($value);
            // }

            $section -> update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new SectionResource($section),
                'message' => 'Section updated successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function trash(Request $request): JsonResponse
    {
        $search = $request->query('search');

        if($search){
            return SectionsResource::collection(Section::onlyTrashed()->where('name', 'like', '%'.$search.'%')->get())
                ->additional([
                    "meta" => [
                        "methods" => "[GET, POST, PUT, DELETE]",
                    ],
                    "message" => "Successfully retrieve all deleted record."
                ])->response();
        }

        return SectionsResource::collection(Section::onlyTrashed()->get())
            ->additional([
                "meta" => [
                    "methods" => "[GET, POST, PUT, DELETE]",
                ],
                "message" => "Successfully retrieve all deleted record."
            ])->response();
    }

    public function restore($id, Request $request)
    {
        Section::withTrashed()->find($id)->restore();

        return (new SectionsResource(Section::find($id)))
            ->additional([
                'meta' => [
                    'methods' => '[GET, POST, PUT, DELETE]'
                ],
                'message' => 'Successfully restore.'
            ]);
    }
    
    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $section = Section::findOrFail($id);

            if(!$section)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $section -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Section deleted successfully.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
