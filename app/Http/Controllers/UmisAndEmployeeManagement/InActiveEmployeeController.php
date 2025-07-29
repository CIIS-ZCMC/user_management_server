<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\CivilServiceEligibilityManyRequest;
use App\Http\Requests\ContactRequest;
use App\Http\Requests\EducationalBackgroundRequest;
use App\Http\Requests\EmployeeProfileNewResource;
use App\Http\Requests\FamilyBackgroundRequest;
use App\Http\Requests\IdentificationNumberRequest;
use App\Http\Requests\LegalInformationManyRequest;
use App\Http\Requests\OtherInformationManyRequest;
use App\Http\Requests\PersonalInformationRequest;
use App\Http\Requests\PersonalInformationUpdateRequest;
use App\Http\Requests\ReferenceManyRequest;
use App\Http\Requests\TrainingManyRequest;
use App\Http\Requests\VoluntaryWorkRequest;
use App\Http\Requests\WorkExperienceRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\ChildResource;
use App\Http\Resources\CivilServiceEligibilityResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\EducationalBackgroundResource;
use App\Http\Resources\EmployeeProfileResource;
use App\Http\Resources\FamilyBackGroundResource;
use App\Http\Resources\IdentificationNumberResource;
use App\Http\Resources\OtherInformationResource;
use App\Http\Resources\TrainingResource;
use App\Http\Resources\VoluntaryWorkResource;
use App\Http\Resources\WorkExperienceResource;
use App\Jobs\SendEmailJob;
use App\Methods\MailConfig;
use App\Models\AssignArea;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeSchedule;
use App\Models\EmploymentType;
use App\Models\LeaveType;
use App\Models\PlantillaAssignedArea;
use App\Models\PlantillaNumber;
use App\Models\Role;
use App\Models\SpecialAccessRole;
use App\Models\SystemRole;
use App\Models\WorkExperience;
use Carbon\Carbon;
use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Resources\InActiveEmployeeResource;
use App\Http\Resources\AssignAreaResource;
use App\Models\InActiveEmployee;
use App\Models\AssignAreaTrail;
use App\Models\EmployeeProfile;

class InActiveEmployeeController extends Controller
{
    private $CONTROLLER_NAME = 'In Active Employee Module';
    private $PLURAL_MODULE_NAME = 'In active employee modules';
    private $SINGULAR_MODULE_NAME = 'In active employee module';
    private $mail;

    public function __construct()
    {
        $this->mail = new MailConfig();
    }

    // In Complete
    public function retireAndDeactivateAccount($id, Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->password);

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'] . config('app.salt_value'), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_FORBIDDEN);
            }

            $employee_profile = EmployeeProfile::find($id);

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $in_active_employee = InActiveEmployee::create([
                'personal_information_id' => $employee_profile->personal_information_id,
                'employment_type_id' => $request->employment_type_id,
                'employee_id' => $employee_profile->employee_id,
                'profile_url' => $employee_profile->profile_url,
                'date_hired' => $employee_profile->date_hired,
                'biometric_id' => $employee_profile->biometric_id,
                'employment_end_at' => now()
            ]);

            $employee_profile->issuanceInformation->update([
                'employee_profile_id' => null,
                'in_active_employee_id' => $in_active_employee->id
            ]);

            $assign_area = $employee_profile->assignedArea;
            $assign_area_trail = AssignAreaTrail::create([
                'employee_profile_id' => null,
                'in_active_employee_id' => $in_active_employee->id,
                'designation_id' => $assign_area->designation_id,
                'plantilla_id' => $assign_area->plantilla_id,
                'division_id' => $assign_area->division_id,
                'department_id' => $assign_area->department_id,
                'section_id' => $assign_area->section_id,
                'unit_id' => $assign_area->unit_id,
                'plantilla_number_id' => $assign_area->plantilla_number_id,
                'salary_grade_step' => $assign_area->salary_grade_step,
                'started_at' => $assign_area->effective_at,
                'end_at' => now()
            ]);

            $assign_area->delete();
            $employee_profile->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in fetching a ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json([
                'data' => new InActiveEmployeeResource($in_active_employee),
                'message' => 'Employee record transfer to in active employees.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'retireAndDeactivateAccount', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index(Request $request)
    {
        try{
            $in_active_employees = InActiveEmployee::all();

            return response()->json([
                'data' => InActiveEmployeeResource::collection($in_active_employees), 
                'message' => 'Record of in-active employee history retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID($id, Request $request)
    {
        try{
            $employe_profile = EmployeeProfile::where('employee_id', $id)->first();

            if(!$employe_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $in_active_employee = InActiveEmployee::where('employee_profile_id',$employe_profile['id'])->first();

            if(!$in_active_employee)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new InActiveEmployeeResource($in_active_employee), 'message' => 'Employee profile found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByAssignedAreaEmployeeID($id, Request $request)
    {
        try{
            $employe_profile = EmployeeProfile::where('employee_id',$id)->first();

            if(!$employe_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $in_active_employee = AssignAreaTrail::where('employee_profile_id',$employe_profile['id'])->get();

            if(!$in_active_employee)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new AssignAreaResource($in_active_employee), 'message' => 'Employee assigned area record trail found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // In Complete
    public function reEmploy($id, Request $request)
    {
        try {
            DB::beginTransaction();
            $in_valid_file = false;
            $in_active_employee = InActiveEmployee::find($id);

            if (!$in_active_employee) {
                return response()->json(['message' => "No in active employee with id " . $id], Response::HTTP_NOT_FOUND);
            }

            $employee_profile = $in_active_employee->employeeProfile;
            $personal_information = $in_active_employee->employeeProfile->personalInformation;

            /**
             * Personal Information module. [DONE]
             */
            $personal_information_request = new PersonalInformationUpdateRequest();
            $personal_information_json = json_decode($request->personal_information);
            $personal_information_data = [];

            foreach ($personal_information_json as $key => $value) {
                if(Str::contains($key, "_value")) continue;
                $personal_information_data[$key] = $value;
            }

            $personal_information_request->merge($personal_information_data);
            $personal_information_controller = new PersonalInformationController();
            $personal_information = $personal_information_controller->update($personal_information->id, $personal_information_request);

            /**
             * Contact module. [DONE]
             */
            $contact_request = new ContactRequest();
            $contact_json = json_decode($request->contact);
            $contact_data = [];

            foreach ($contact_json as $key => $value) {
                $contact_data[$key] = $value;
            }

            $contact_id = $personal_information->contact->id;

            $contact_request->merge($contact_data);
            $contact_controller = new ContactController();
            $contact_controller->update($contact_id, $contact_request);

            /**
             * Family background module [DONE]
             */

            $family_background_request = new FamilyBackgroundRequest();
            $family_background_json = json_decode($request->family_background);
            $family_background_data = [];

            foreach ($family_background_json as $key => $value) {
                $family_background_data[$key] = $value;
            }

            $family_background_request->merge($family_background_data);
            $family_background_request->merge(['personal_info' => $personal_information]);
            $family_background_request->merge(['children' => $request->children]);
            $family_background_controller = new FamilyBackgroundController();
            $family_background_controller->update($family_background_request->id, $family_background_request);

            /**
             * Education module [DONE]
             */
            $education_request = new EducationalBackgroundRequest();
            $education_json = json_decode($request->educations);
            $education_data = [];

            //Update must be done here
            foreach ($education_json as $key => $value) {
                $education_data[$key] = $value;
            }

            $education_request->merge(['educations' => $education_data]);
            $education_controller = new EducationalBackgroundController();
            $education_controller->update($personal_information->id, $education_request);

            /**
             * Identification module [DONE]
             */
            $identification_request = new IdentificationNumberRequest();
            $identification_json = json_decode($request->identification);
            $identification_data = [];
            $identification_id = $personal_information->identificationNumber->id;

            foreach ($identification_json as $key => $value) {
                $identification_data[$key] = $value;
            }

            $identification_request->merge($identification_data);
            $identification_controller = new IdentificationNumberController();
            $identification_controller->update($identification_id, $identification_request);

            /**
             * Work experience module [DONE]
             */
            $work_experience_request = new WorkExperienceRequest();
            $work_experience_json = json_decode($request->work_experiences);
            $work_experience_data = [];

            foreach ($work_experience_json as $key => $value) {
                $work_experience_data[$key] = $value;
            }

            $work_experience_request->merge(['work_experiences' => $work_experience_data]);
            $work_experience_controller = new WorkExperienceController();
            $work_experience_controller->update($personal_information->id, $work_experience_request);

            /**
             * Voluntary work module [DONE]
             */
            $voluntary_work_request = new VoluntaryWorkRequest();
            $voluntary_work_json = json_decode($request->voluntary_work);
            $voluntary_work_data = [];

            foreach ($voluntary_work_json as $key => $value) {
                $voluntary_work_data[$key] = $value;
            }

            $voluntary_work_request->merge(['voluntary_work' => $voluntary_work_data]);
            $voluntary_work_controller = new VoluntaryWorkController();
            $voluntary_work_controller->update($personal_information->id, $voluntary_work_request);

            /**
             * Other module [DONE]
             */
            $other_request = new OtherInformationManyRequest();
            $other_json = json_decode($request->others);
            $other_data = [];

            foreach ($other_json as $key => $value) {
                $voluntary_work_data[$key] = $value;
            }

            $other_request->merge(['others' => $other_data]);
            $other_controller = new OtherInformationController();
            $other_controller->update($personal_information->id, $other_request);

            /**
             * Legal information module [DONE]
             */
            $legal_info_request =  new LegalInformationManyRequest();
            $legal_info_json = json_decode($request->legal_information);
            $legal_info_data = [];

            foreach ($legal_info_json as $key => $value) {
                $legal_info_data[$key] = $value;
            }

            $legal_info_request->merge(['legal_information' => $legal_info_data]);
            $legal_information_controller = new LegalInformationController();
            $legal_information_controller->storeMany($personal_information->id, $legal_info_request);

            /**
             * Training module [DONE]
             */
            $training_request = new TrainingManyRequest();
            $training_json = json_decode($request->trainings);
            $training_data = [];

            foreach ($training_json as $key => $value) {
                $training_data[$key] = $value;
            }

            $training_request->merge(['trainings' => $training_data]);
            $training_controller = new TrainingController();
            $training_controller->update($personal_information->id, $training_request);

            /**
             * Reference module [DONE]
             */
            $referrence_request = new ReferenceManyRequest();
            $referrence_json = json_decode($request->reference);
            $referrence_data = [];

            foreach ($referrence_json as $key => $value) {
                $referrence_data[$key] = $value;
            }

            $referrence_request->merge(['references' => $referrence_data]);
            $referrence_controller = new ReferencesController();
            $referrence_controller->update($personal_information->id, $referrence_request);

            /**
             * Eligibilities module [DONE]
             */
            $eligibilities_request = new CivilServiceEligibilityManyRequest();
            $eligibilities_json = json_decode($request->eligibilities);
            $eligibilities_data = [];

            foreach ($eligibilities_json as $key => $value) {
                $eligibilities_data[$key] = $value;
            }

            $eligibilities_request->merge(['eligibilities' => $eligibilities_data]);
            $eligibilities_controller = new CivilServiceEligibilityController();
            $eligibilities_controller->update($personal_information->id, $eligibilities_request);

            //** Employee Profile Module */

            $previous_employee_profile_id = $in_active_employee->employee_profile_id;

            $dateString = $request->date_hired;
            $carbonDate = Carbon::parse($dateString);
            $date_hired_string = $carbonDate->format('Ymd');

            $total_registered_this_day = EmployeeProfile::whereDate('date_hired', $carbonDate)->get();
            $employee_id_random_digit = 50 + count($total_registered_this_day);

            // $employee_data = $in_active_employee;
            $employee_data['employee_id'] = $employee_id_random_digit;

            $last_registered_employee = EmployeeProfile::orderBy('biometric_id', 'desc')->first();
            $default_password = Helpers::generatePassword();

            $hashPassword = Hash::make($default_password . config('app.salt_value'));
            $encryptedPassword = Crypt::encryptString($hashPassword);
            $now = Carbon::now();
            $threeMonths = Carbon::now()->addMonths(3);

            $new_biometric_id = $last_registered_employee->biometric_id + 1;
            $new_employee_id = $date_hired_string . $employee_id_random_digit;

            $employee_data['employee_id'] = strip_tags($request->personal_information->employee_id);
            $employee_data['biometric_id'] = $new_biometric_id;
            $employee_data['employment_type_id'] = strip_tags($request->employment_type_id);

            try {
                $fileName = Helpers::checkSaveFile($request->attachment, 'photo/profiles');
                if (is_string($fileName)) {
                    $employee_data['profile_url'] = $request->attachment === null || $request->attachment === 'null' ? null : $fileName;
                }

                if (is_array($fileName)) {
                    $in_valid_file = true;
                    $employee_data['profile_url'] = null;
                }
            } catch (\Throwable $th) {}

            $employee_data['allow_time_adjustment'] = strip_tags($request->allow_time_adjustment) === 1 ? true : false;
            $employee_data['solo_parent'] = strip_tags($request->solo_parent) === 1 ? true : false;
            $employee_data['password_encrypted'] = $encryptedPassword;
            $employee_data['password_created_at'] = now();
            $employee_data['password_expiration_at'] = $threeMonths;
            $employee_data['salary_grade_step'] = strip_tags($request->salary_grade_step);
            $employee_data['date_hired'] = $request->date_hired;
            $employee_data['designation_id'] = $request->designation_id;
            $employee_data['effective_at'] = $request->date_hired;
            $employee_data['deactivated_at'] = null;

            if(EmploymentType::find($employee_data['employment_type_id'])->name === 'Temporary' || EmploymentType::find($employee_data['employment_type_id'])->name === 'Job Order'){

                if(EmploymentType::find($employee_data['employment_type_id'])->name === 'Temporary'){
                    $employee_data['renewal'] = Carbon::now()->addYear();
                }
            }

            $plantilla_number_id = $request->plantilla_number_id === "null" || $request->plantilla_number_id === null ? null : $request->plantilla_number_id;

            $employee_data[Str::lower(strip_tags($request->sector))."_id"] = strip_tags($request->sector_id);

            if ($plantilla_number_id !== null) {
                $plantilla_number = PlantillaNumber::find($plantilla_number_id);

                if (!$plantilla_number) {
                    return response()->json(['message' => 'No record found for plantilla number ' . $plantilla_number_id], Response::HTTP_NOT_FOUND);
                }
                
                $key = strtolower($request->sector).'_id';
                $cleanData[$key] = strip_tags($request->area_id);
                $cleanData['plantilla_number_id'] = $plantilla_number->id;

                $key_list = ['division_id', 'department_id', 'section_id', 'unit_id'];

                foreach ($key_list as $value) {
                    if ($value === $key) continue;
                    $cleanData[$value] = null;
                }

                $plantilla_assign_area = PlantillaAssignedArea::create($cleanData);
                $plantilla_number->update(['assigned_at' => now()]);

                $plantilla = $plantilla_number->plantilla;
                $designation = $plantilla->designation;
                $employee_data['designation_id'] = $designation->id;
                $employee_data['plantilla_number_id'] = $plantilla_number->id;
            }

            $employee_profile->update($employee_data);

            $employee_data['employee_profile_id'] = $employee_profile->id;
            AssignArea::create($employee_data);

            if ($plantilla_number_id !== null) {
                $plantilla_number = PlantillaNumber::find($plantilla_number_id);
                $plantilla_number->update(['employee_profile_id' => $employee_profile->id, 'is_vacant' => false, 'assigned_at' => now()]);
            }

            if ($plantilla_number_id !== null) {
                $leave_types = LeaveType::where('is_special', 0)->get();

                foreach ($leave_types as $leave_type) {
                    EmployeeLeaveCredit::create([
                        'employee_profile_id' => $employee_profile->id,
                        'leave_type_id' => $leave_type->id,
                        'total_leave_credits' => 0,
                        'used_leave_credits' => 0
                    ]);
                }
                $currentYear = date('Y');
                $validUntil = date('Y-m-d', strtotime("$currentYear-12-31"));

                EmployeeOvertimeCredit::create([
                    'employee_profile_id' => $employee_profile->id,
                    'earned_credit_by_hour' => 0,
                    'used_credit_by_hour' => 0,
                    'valid_until' => $validUntil,
                    'is_expired' => 0,
                    'max_credit_monthly' => 40,
                    'max_credit_annual' => 120
                ]);
            }
            
            if (strip_tags($request->shifting) === "0") {
                $schedule_this_month = Helpers::generateSchedule(Carbon::now(), $employee_data['employment_type_id'], $request->meridian);

                foreach ($schedule_this_month as $schedule) {
                    EmployeeSchedule::create([
                        'employee_profile_id' => $employee_profile->id,
                        'schedule_id' => $schedule->id
                    ]);
                }

                $schedule_next_month = Helpers::generateSchedule(Carbon::now()->addMonth()->startOfMonth(), $employee_data['employment_type_id'], $request->meridian);

                foreach ($schedule_next_month as $schedule) {
                    EmployeeSchedule::create([
                        'employee_profile_id' => $employee_profile->id,
                        'schedule_id' => $schedule->id
                    ]);
                }
            } else {
                
                $role = Role::where('code', 'SHIFTING')->first();
                $system_role = SystemRole::where('role_id', $role->id)->first();

                SpecialAccessRole::create([
                    'system_role_id' => $system_role->id,
                    'employee_profile_id' => $employee_profile->id,
                    'effective_at' => now()
                ]);
            }

            if (strip_tags($request->allow_time_adjustment) === 1) {
                $role = Role::where('code', 'ATA')->first();
                $system_role = SystemRole::where('role_id', $role->id)->first();

                SpecialAccessRole::create([
                    'system_role_id' => $system_role->id,
                    'employee_profile_id' => $employee_profile->id,
                    'effective_at' => now()
                ]);
            }

            $in_active_employee->delete();
            Helpers::registerSystemLogs($request, $employee_profile->id, true, 'Success in creating a ' . $this->SINGULAR_MODULE_NAME . '.');

            $data = [
                'employeeID' => $employee_profile->employee_id,
                'Password' => $default_password,
                "Link" => config('app.client_domain')
            ];

            $email = $employee_profile->personalinformation->contact->email_address;
            $name = $employee_profile->personalInformation->name();

            SendEmailJob::dispatch('new_account', $email, $name, $data);
            DB::commit();

            if ($in_valid_file) {
                return response()->json(
                    [
                        'data' => new EmployeeProfileResource($employee_profile),
                        'message' => 'Newly employee registered.',
                        'other' => "Invalid attachment."
                    ],
                    Response::HTTP_OK
                );
            }

            return response()->json(
                [
                    'data' => new EmployeeProfileResource($employee_profile),
                    'message' => 'Newly employee registered.'
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'reEmploy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showProfile($id, Request $request)
    {
        try {

            $in_active_employee = InActiveEmployee::find($id);

            if (!$in_active_employee) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            Helpers::registerSystemLogs($request, $id, true, 'Success in fetching a ' . $this->SINGULAR_MODULE_NAME . '.');
        
            $employee_profile = $in_active_employee->employeeProfile;
            $personal_information = $employee_profile->personalInformation;

            $work_experiences = WorkExperience::where('personal_information_id', $personal_information->id)->where('government_office', "Yes")->get();

            $totalMonths = 0; // Initialize total months variable
            $totalYears = 0; // Initialize total months variable

            foreach ($work_experiences as $work) {
                $dateFrom = Carbon::parse($work->date_from);
                $dateTo = Carbon::parse($work->date_to);
                $months = $dateFrom->diffInMonths($dateTo);
                $totalMonths += $months;
            }

            $totalYears = floor($totalMonths / 12);

            $personal_information_data = [
                'personal_information_id' => $personal_information->id,
                'full_name' => $personal_information->nameWithSurnameFirst(),
                'first_name' => $personal_information->first_name,
                'last_name' => $personal_information->last_name,
                'middle_name' => $personal_information->middle_name === null ? ' ' : $personal_information->middle_name,
                'name_extension' => $personal_information->name_extension === null ? null : $personal_information->name_extension,
                'employee_id' => $in_active_employee->employee_id,
                'years_of_service' => $personal_information->years_of_service === null ? null : $personal_information->years_of_service,
                'name_title' => $personal_information->name_title === null ? null : $personal_information->name_title,
                'sex' => $personal_information->sex,
                'date_of_birth' => $personal_information->date_of_birth,
                'place_of_birth' => $personal_information->place_of_birth,
                'civil_status' => $personal_information->civil_status,
                'citizenship' => $personal_information->citizenship,
                'date_of_marriage' => $personal_information->date_of_marriage === null ? null : $personal_information->date_of_marriage,
                'blood_type' => $personal_information->blood_type === null ? null : $personal_information->blood_type,
                'height' => $personal_information->height,
                'weight' => $personal_information->weight,
            ];

            $address = [
                'residential_address' => null,
                'residential_zip_code' => null,
                'residential_telephone_no' => null,
                'permanent_address' => null,
                'permanent_zip_code' => null,
                'permanent_telephone_no' => null
            ];

            $addresses = $personal_information->addresses;

            foreach ($addresses as $value) {

                if ($value->is_residential_and_permanent) {
                    $address['residential_address'] = $value->address;
                    $address['residential_zip_code'] = $value->zip_code;
                    $address['residential_telephone_no'] = $value->telephone_no;
                    $address['permanent_address'] = $value->address;
                    $address['permanent_zip_code'] = $value->zip_code;
                    $address['permanent_telephone_no'] = $value->telephone_no;
                    break;
                }

                if ($value->is_residential) {
                    $address['residential_address'] = $value->address;
                    $address['residential_zip_code'] = $value->zip_code;
                    $address['residential_telephone_no'] = $value->telephone_no;
                } else {
                    $address['permanent_address'] = $value->address;
                    $address['permanent_zip_code'] = $value->zip_code;
                    $address['permanent_telephone_no'] = $value->telephone_no;
                }
            }

            $data = [
                'personal_information_id' => $personal_information->id,
                'in_active_employee_id' => $in_active_employee['id'],
                'employee_id' => $in_active_employee['employee_id'],
                'name' => $personal_information->employeeName(),
                'employee_details' => [
                    'personal_information' => $personal_information_data,
                    'personal_information_id' => $personal_information->id,
                    'contact' => new ContactResource($personal_information->contact),
                    'address' => $address,
                    'address_update' => AddressResource::collection($personal_information->addresses),
                    'family_background' => new FamilyBackGroundResource($personal_information->familyBackground),
                    'children' => ChildResource::collection($personal_information->children),
                    'education' => EducationalBackgroundResource::collection($personal_information->educationalBackground),
                    'affiliations_and_others' => [
                        'civil_service_eligibility' => CivilServiceEligibilityResource::collection($personal_information->civilServiceEligibility),
                        'work_experience' => WorkExperienceResource::collection($personal_information->workExperience),
                        'voluntary_work_or_involvement' => VoluntaryWorkResource::collection($personal_information->voluntaryWork),
                        'training' => TrainingResource::collection($personal_information->training),
                        'other' => OtherInformationResource::collection($personal_information->otherInformation),
                    ],
                    'identification' => new IdentificationNumberResource($personal_information->identificationNumber)
                ]
            ];
            return response()->json(['data' => $data, 'message' => 'Employee details found.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'showProfile', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $in_active_employee = InActiveEmployee::find($id);

            if(!$in_active_employee)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new InActiveEmployeeResource($in_active_employee), 'message' => 'Employee in-active record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
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

            $in_active_employee = InActiveEmployee::findOrFail($id);

            if(!$in_active_employee)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $in_active_employee->delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => 'Employee in-active record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
