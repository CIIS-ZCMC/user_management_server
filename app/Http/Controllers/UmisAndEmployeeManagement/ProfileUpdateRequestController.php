<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

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
    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
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
}
