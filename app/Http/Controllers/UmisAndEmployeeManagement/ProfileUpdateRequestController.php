<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\PersonalInformationRequest;
use App\Models\Address;
use App\Models\Child;
use App\Models\CivilServiceEligibility;
use App\Models\Contact;
use App\Models\EducationalBackground;
use App\Models\FamilyBackground;
use App\Models\IdentificationNumber;
use App\Models\OtherInformation;
use App\Models\PersonalInformation;
use App\Models\Training;
use App\Models\VoluntaryWork;
use App\Models\WorkExperience;
use App\Services\FileValidationAndUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\RequestLogger;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\ProfileUpdateRequestResource;
use App\Models\ProfileUpdateRequest;

class ProfileUpdateRequestController extends Controller
{
    private $CONTROLLER_NAME = 'Profile Update Request';
    private $PLURAL_MODULE_NAME = 'profile update requests';
    private $SINGULAR_MODULE_NAME = 'profile update request';

    protected $requestLogger;
    protected $fileValidateAndUpload;

    public function __construct(RequestLogger $requestLogger, FileValidationAndUpload $fileValidateAndUpload)
    {
        $this->requestLogger = $requestLogger;
        $this->fileValidateAndUpload = $fileValidateAndUpload;
    }

    public function index(Request $request)
    {
        try{
            $profile_update_request = ProfileUpdateRequest::all();

            return response()->json([
                'data' => ProfileUpdateRequestResource::collection($profile_update_request),
                'message' => 'Request records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function pending(Request $request)
    {
        try{
            $profile_update_request = ProfileUpdateRequest::where('approved_by', null)->get();

            return response()->json([
                'data' => ProfileUpdateRequestResource::collection($profile_update_request),
                'message' => 'Request records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function request(Request $request)
    {
        $table = strip_tags($request->table_name);

        switch($table){
            case 'Profile Information':
                $new_profile_information = $this->updateEmployeePersonalInformation($request);
                break;
            case 'Educational Background':
                $educational_background = $this->requestUpdateEducationalBackground($request);
                break;
            case 'Child':
                $child = $this->requestUpdateChildInformation($request);
                break;
            case 'Address':
                $address = $this->requestUpdateAddressInformation($request);
                break;
            case 'Contact':
                $contact = $this->requestUpdateContactInformation($request);
                break;
            case 'Family Background':
                $family_background = $this->requestUpdateFamilyBackgroundInformation($request);
                break;
            case 'Identication':
                $identication = $this->requestUpdateIdentificationInformation($request);
                break;
            case 'Eligibility':
                $eligibility = $this->requestUpdateEligibilityInformation($request);
                break;
            case 'Training':
                $training = $this->requestUpdateTrainingInformation($request);
                break;
            case 'Work Experience':
                $work_experience = $this->requestUpdateWorkExperienceInformation($request);
                break;
            case 'Voluntary Work';
                $voluntary_work = $this->requestUpdateVoluntaryWorkInformation($request);
                break;
            case 'Other':
                $other = $this->requestUpdateOtherInformation($request);
                break;
            default: 
                return response()->json(['message' => 'Table name is not found.'], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['message' => "Request for personal information update successfully created, please wait for approval."], Response::HTTP_CREATED);
    }
    
    public function requestUpdatePersonalInformation(Request $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'attachment'){
                    $cleanData[$key] = $this->fileValidateAndUpload->check_save_file($request, 'employee/profiles');
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $personal_information = PersonalInformation::create($cleanData);

            $profile_update_request = ProfileUpdateRequest::create([
                'employee_profile_id' => strip_tags($request->employee_profile_id),
                'approved_by' => null,
                'table_name' => 'Personal Information',
                'data_id' => $personal_information->id,
                'target_id' => null,
                'type_new_or_replace' => strip_tags($request->type_new_or_replace)
            ]);
            
            $this->requestLogger->registerSystemLogs($request, $request->employee_profile_id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return $profile_update_request;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'requestUpdatePersonalInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function requestUpdateEducationalBackground(Request $request)
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

            $educational_background = EducationalBackground::create($cleanData);

            $profile_update_request = ProfileUpdateRequest::create([
                'employee_profile_id' => strip_tags($request->employee_profile_id),
                'approved_by' => null,
                'table_name' => 'Educational Background',
                'data_id' => $educational_background->id,
                'target_id' => strip_tags($request->target_id),
                'type_new_or_replace' => strip_tags($request->type_new_or_replace)
            ]);
            
            $this->requestLogger->registerSystemLogs($request, $request->employee_profile_id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
           
            return $profile_update_request;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'requestUpdateEducationalBackground', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function requestUpdateChildInformation(Request $request)
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

            $child = Child::create($cleanData);

            $profile_update_request = ProfileUpdateRequest::create([
                'employee_profile_id' => strip_tags($request->employee_profile_id),
                'approved_by' => null,
                'table_name' => 'Educational Background',
                'data_id' => $child->id,
                'target_id' => strip_tags($request->target_id),
                'type_new_or_replace' => strip_tags($request->type_new_or_replace)
            ]);

            $this->requestLogger->registerSystemLogs($request, $request->employee_profile_id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return $profile_update_request;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'requestUpdateChildInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function requestUpdateAddressInformation(Request $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (is_bool($value) || $value === null) {
                    $cleanData[$key] = $value;
                } else {
                    $cleanData[$key] = strip_tags($value);
                }
            }

            $address = Address::create($cleanData);

            $profile_update_request = ProfileUpdateRequest::create([
                'employee_profile_id' => strip_tags($request->employee_profile_id),
                'approved_by' => null,
                'table_name' => 'Educational Background',
                'data_id' => $address->id,
                'target_id' => strip_tags($request->target_id),
                'type_new_or_replace' => strip_tags($request->type_new_or_replace)
            ]);

            $this->requestLogger->registerSystemLogs($request, $request->employee_profile_id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return $profile_update_request;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'requestUpdateAddressInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function requestUpdateContactInformation(Request $request)
    {
        try{ 
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $contact = Contact::create($cleanData);

            $profile_update_request = ProfileUpdateRequest::create([
                'employee_profile_id' => strip_tags($request->employee_profile_id),
                'approved_by' => null,
                'table_name' => 'Educational Background',
                'data_id' => $contact->id,
                'target_id' => strip_tags($request->target_id),
                'type_new_or_replace' => strip_tags($request->type_new_or_replace)
            ]);

            $this->requestLogger->registerSystemLogs($request, $request->employee_profile_id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return $profile_update_request;
        }catch(\Throwable $th){
           $this->requestLogger->errorLog($this->CONTROLLER_NAME,'requestUpdateContactInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function requestUpdateFamilyBackgroundInformation(Request $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'user') continue;
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'tin_no' || $key === 'rdo_no'){
                    $cleanData[$key] = $this->encryptData($value);
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $family_background = FamilyBackground::create($cleanData);
 
            $cleanData['$personal_information_id'] = null;

            $profile_update_request = ProfileUpdateRequest::create([
                'employee_profile_id' => strip_tags($request->employee_profile_id),
                'approved_by' => null,
                'table_name' => 'Educational Background',
                'data_id' => $family_background->id,
                'target_id' => strip_tags($request->target_id),
                'type_new_or_replace' => strip_tags($request->type_new_or_replace)
            ]);

            $this->requestLogger->registerSystemLogs($request, $request->employee_profile_id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return $profile_update_request;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'requestUpdateFamilyBackgroundInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function requestUpdateIdentificationInformation(Request $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'personal_information_id'){ 
                    $cleanData[$key] = null;
                    continue;
                }
                $cleanData[$key] =  $this->encryptData(strip_tags($value));
            }

            $identification = IdentificationNumber::create($cleanData);

            $profile_update_request = ProfileUpdateRequest::create([
                'employee_profile_id' => strip_tags($request->employee_profile_id),
                'approved_by' => null,
                'table_name' => 'Educational Background',
                'data_id' => $identification->id,
                'target_id' => strip_tags($request->target_id),
                'type_new_or_replace' => strip_tags($request->type_new_or_replace)
            ]);
            
            $this->requestLogger->registerSystemLogs($request, $request->employee_profile_id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
 
            return $profile_update_request;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'requestUpdateIdentificationInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function requestUpdateEligibilityInformation(Request $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null)
                {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $civil_service_eligibility = CivilServiceEligibility::create($cleanData);

            $profile_update_request = ProfileUpdateRequest::create([
                'employee_profile_id' => strip_tags($request->employee_profile_id),
                'approved_by' => null,
                'table_name' => 'Educational Background',
                'data_id' => $civil_service_eligibility->id,
                'target_id' => strip_tags($request->target_id),
                'type_new_or_replace' => strip_tags($request->type_new_or_replace)
            ]);

            $this->requestLogger->registerSystemLogs($request, $request->employee_profile_id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return $profile_update_request;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'requestUpdateEligibilityInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function requestUpdateTrainingInformation(Request $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'type_is_lnd'){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $training = Training::create($cleanData);

            $profile_update_request = ProfileUpdateRequest::create([
                'employee_profile_id' => strip_tags($request->employee_profile_id),
                'approved_by' => null,
                'table_name' => 'Educational Background',
                'data_id' => $training->id,
                'target_id' => strip_tags($request->target_id),
                'type_new_or_replace' => strip_tags($request->type_new_or_replace)
            ]);

            $this->requestLogger->registerSystemLogs($request, $request->employee_profile_id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return $profile_update_request;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'requestUpdateTrainingInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function requestUpdateVoluntaryWorkInformation(Request $request)
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

            $profile_update_request = ProfileUpdateRequest::create([
                'employee_profile_id' => strip_tags($request->employee_profile_id),
                'approved_by' => null,
                'table_name' => 'Educational Background',
                'data_id' => $voluntary_work->id,
                'target_id' => strip_tags($request->target_id),
                'type_new_or_replace' => strip_tags($request->type_new_or_replace)
            ]);

            $this->requestLogger->registerSystemLogs($request, $request->employee_profile_id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
 
            return $profile_update_request;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'requestUpdateVoluntaryWorkInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function requestUpdateWorkExperienceInformation(Request $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value===null){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $work_experience = WorkExperience::create($cleanData);

            $profile_update_request = ProfileUpdateRequest::create([
                'employee_profile_id' => strip_tags($request->employee_profile_id),
                'approved_by' => null,
                'table_name' => 'Educational Background',
                'data_id' => $work_experience->id,
                'target_id' => strip_tags($request->target_id),
                'type_new_or_replace' => strip_tags($request->type_new_or_replace)
            ]);

            $this->requestLogger->registerSystemLogs($request, $request->employee_profile_id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return $profile_update_request;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function requestUpdateOtherInformation(Request $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $other_information = OtherInformation::create($cleanData);

            $profile_update_request = ProfileUpdateRequest::create([
                'employee_profile_id' => strip_tags($request->employee_profile_id),
                'approved_by' => null,
                'table_name' => 'Educational Background',
                'data_id' => $other_information->id,
                'target_id' => strip_tags($request->target_id),
                'type_new_or_replace' => strip_tags($request->type_new_or_replace)
            ]);

            $this->requestLogger->registerSystemLogs($request, $request->employee_profile_id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return $profile_update_request;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'requestUpdateOtherInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approveRequest($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $profile_update_request = ProfileUpdateRequest::find($id);

            if($profile_update_request){
                return response()->json(['message' => 'No existing request with id '.$id], Response::HTTP_NOT_FOUND);
            }

            switch($profile_update_request->table_name){
                case 'Profile Information':
                    $new_profile_information = $this->updateEmployeePersonalInformation($profile_update_request);
                    break;
                case 'Educational Background':
                    $educational_background = $this->updateEducationalBackgrounds($profile_update_request);
                    break;
                case 'Child':
                    $child = $this->updateChild($profile_update_request);
                    break;
                case 'Address':
                    $address = $this->updateAddress($profile_update_request);
                    break;
                case 'Contact':
                    $contact = $this->updateContact($profile_update_request);
                    break;
                case 'Family Background':
                    $family_background = $this->updateFamilyBackground($profile_update_request);
                    break;
                case 'Identication':
                    $identication = $this->updateIdentification($profile_update_request);
                    break;
                case 'Eligibility':
                    $eligibility = $this->updateCivilServiceEligibilities($profile_update_request);
                    break;
                case 'Training':
                    $training = $this->updateTraining($profile_update_request);
                    break;
                case 'Work Experience':
                    $work_experience = $this->updateWorkExperience($profile_update_request);
                    break;
                case 'Voluntary Work';
                    $voluntary_work = $this->updateVoluntaryWork($profile_update_request);
                    break;
                case 'Other':
                    $other = $this->updateOtherInformation($profile_update_request);
                    break;
                default: 
                    return response()->json(['message' => 'Table name is not found.'], Response::HTTP_BAD_REQUEST);
            }

            $profile_update_request->update(['approved_by' => $employee_profile->id]);

            return response()->json(['message' => 'Request approved and changes applied.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'approveRequest', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function updateEmployeePersonalInformation($profile_update_request)
    {
        $employee = $profile_update_request->employee;
        $personal_information = $employee->personalInformation;

        $requested_personal_information = PersonalInformation::find($profile_update_request->data_id);

        $new_data = [];

        foreach($requested_personal_information as $key => $value){
            $new_data[$key] = $value;
        }

        $personal_information->update($new_data);
        
        return $personal_information;
    }

    protected function updateEducationalBackgrounds($profile_update_request)
    {
        $requested_personal_information = EducationalBackground::find($profile_update_request->data_id);
        $new_data = [];

        foreach($requested_personal_information as $key => $value){
            $new_data[$key] = $value;
        }

        if($profile_update_request->type_new_or_replace){
            $new_data['personal_information_id'] = $profile_update_request->employee->personalInformation->id;
            return EducationalBackground::create($new_data);
        }

        return EducationalBackground::find($profile_update_request->target_id)->update($new_data);
    }
    
    protected function updateChild($profile_update_request)
    {
        $requested_personal_information = Child::find($profile_update_request->data_id);
        $new_data = [];

        foreach($requested_personal_information as $key => $value){
            $new_data[$key] = $value;
        }

        if($profile_update_request->type_new_or_replace){
            $new_data['personal_information_id'] = $profile_update_request->employee->personalInformation->id;
            return Child::create($new_data);
        }

        return Child::find($profile_update_request->target_id)->update($new_data);
    }

    protected function updateAddress($profile_update_request)
    {
        $requested_personal_information = Address::find($profile_update_request->data_id);
        $new_data = [];

        foreach($requested_personal_information as $key => $value){
            $new_data[$key] = $value;
        }

        if($profile_update_request->type_new_or_replace){
            $new_data['personal_information_id'] = $profile_update_request->employee->personalInformation->id;
            return Address::create($new_data);
        }

        return Address::find($profile_update_request->target_id)->update($new_data);
    }

    protected function updateContact($profile_update_request)
    {
        $requested_personal_information = Contact::find($profile_update_request->data_id);
        $new_data = [];

        foreach($requested_personal_information as $key => $value){
            $new_data[$key] = $value;
        }

        if($profile_update_request->type_new_or_replace){
            $new_data['personal_information_id'] = $profile_update_request->employee->personalInformation->id;
            return Contact::create($new_data);
        }

        return Contact::find($profile_update_request->target_id)->update($new_data);
    }

    protected function updateFamilyBackground($profile_update_request)
    {
        $requested_personal_information = FamilyBackground::find($profile_update_request->data_id);
        $new_data = [];

        foreach($requested_personal_information as $key => $value){
            $new_data[$key] = $value;
        }

        if($profile_update_request->type_new_or_replace){
            $new_data['personal_information_id'] = $profile_update_request->employee->personalInformation->id;
            return FamilyBackground::create($new_data);
        }

        return FamilyBackground::find($profile_update_request->target_id)->update($new_data);
    }

    protected function updateIdentification($profile_update_request)
    {
        $requested_personal_information = IdentificationNumber::find($profile_update_request->data_id);
        $new_data = [];

        foreach($requested_personal_information as $key => $value){
            $new_data[$key] = openssl_encrypt($value, env("ENCRYPT_DECRYPT_ALGORITHM"), env("DATA_KEY_ENCRYPTION"), 0, substr(md5(env("DATA_KEY_ENCRYPTION")), 0, 16));
        }

        if($profile_update_request->type_new_or_replace){
            $new_data['personal_information_id'] = $profile_update_request->employee->personalInformation->id;
            return IdentificationNumber::create($new_data);
        }

        return IdentificationNumber::find($profile_update_request->target_id)->update($new_data);
    }

    protected function updateCivilServiceEligibilities($profile_update_request)
    {
        $requested_personal_information = CivilServiceEligibility::find($profile_update_request->data_id);
        $new_data = [];

        foreach($requested_personal_information as $key => $value){
            $new_data[$key] = $value;
        }

        if($profile_update_request->type_new_or_replace){
            $new_data['personal_information_id'] = $profile_update_request->employee->personalInformation->id;
            return CivilServiceEligibility::create($new_data);
        }

        return CivilServiceEligibility::find($profile_update_request->target_id)->update($new_data);
    }

    protected function updateTraining($profile_update_request)
    {
        $requested_personal_information = Training::find($profile_update_request->data_id);
        $new_data = [];

        foreach($requested_personal_information as $key => $value){
            $new_data[$key] = $value;
        }

        if($profile_update_request->type_new_or_replace){
            $new_data['personal_information_id'] = $profile_update_request->employee->personalInformation->id;
            return Training::create($new_data);
        }

        return Training::find($profile_update_request->target_id)->update($new_data);
    }

    protected function updateWorkExperience($profile_update_request)
    {
        $requested_personal_information = WorkExperience::find($profile_update_request->data_id);
        $new_data = [];

        foreach($requested_personal_information as $key => $value){
            $new_data[$key] = $value;
        }

        if($profile_update_request->type_new_or_replace){
            $new_data['personal_information_id'] = $profile_update_request->employee->personalInformation->id;
            return WorkExperience::create($new_data);
        }

        return WorkExperience::find($profile_update_request->target_id)->update($new_data);
    }

    protected function updateVoluntaryWork($profile_update_request)
    {
        $requested_personal_information = VoluntaryWork::find($profile_update_request->data_id);
        $new_data = [];

        foreach($requested_personal_information as $key => $value){
            $new_data[$key] = $value;
        }

        if($profile_update_request->type_new_or_replace){
            $new_data['personal_information_id'] = $profile_update_request->employee->personalInformation->id;
            return VoluntaryWork::create($new_data);
        }

        return VoluntaryWork::find($profile_update_request->target_id)->update($new_data);
    }

    protected function updateOtherInformation($profile_update_request)
    {
        $requested_personal_information = OtherInformation::find($profile_update_request->data_id);
        $new_data = [];

        foreach($requested_personal_information as $key => $value){
            $new_data[$key] = $value;
        }

        if($profile_update_request->type_new_or_replace){
            $new_data['personal_information_id'] = $profile_update_request->employee->personalInformation->id;
            return OtherInformation::create($new_data);
        }

        return OtherInformation::find($profile_update_request->target_id)->update($new_data);
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

            $profile_update_request = ProfileUpdateRequest::findOrFail($id);

            if(!$profile_update_request)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $profile_update_request->delete();
            
            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee child record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
