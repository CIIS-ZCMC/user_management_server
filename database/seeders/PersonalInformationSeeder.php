<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Address;
use App\Models\AssignArea;
use App\Models\Contact;
use App\Models\Designation;
use App\Models\EducationalBackground;
use App\Models\EmployeeProfile;
use App\Models\EmploymentType;
use App\Models\FamilyBackground;
use App\Models\IdentificationNumber;
use App\Models\IssuanceInformation;
use App\Models\LegalInformation;
use App\Models\LegalInformationQuestion;
use App\Models\Reference;
use App\Models\PersonalInformation;
use App\Models\OtherInformation;
use App\Models\Section;

class PersonalInformationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $personal_information = PersonalInformation::create([
            'first_name' => 'Tristan jay',
            'last_name' => 'Amit',
            'sex' => 'Male',
            'date_of_birth' => Carbon::createFromFormat('Y-m-d', "2001-01-23"),
            'place_of_birth' => 'Zamboanga City',
            'civil_status' => 'Single',
            'height' => 172,
            'weight' => 54,
            'blood_type' => 'A'
        ]);

        Address::create([
            'address' => 'San Roque, Zamboanga City',
            'is_residential_and_permanent' => true,
            'is_residential' => true,
            'personal_information_id' => $personal_information->id,
        ]);

        Contact::create([
            'phone_number' => '09123456789',
            'email_address' => 'tristan.zcmc@gmail.com',
            'personal_information_id' => $personal_information->id,
        ]);

        FamilyBackground::create([
            'father_first_name' => 'Juan',
            'father_last_name' => 'Ponce',
            'mother_first_name' => 'Maria',
            'mother_last_name' => 'Ponce',
            'personal_information_id' => $personal_information->id,
        ]);

        Reference::create([
            'name' => 'Kim Ponce',
            'address' => 'Putik, Zamboanga City',
            'contact_no' => '09123456789',
            'personal_information_id' => $personal_information->id,
        ]);

        $encrypted_data = $this->encryptData('87654321');

        IdentificationNumber::create([
            'gsis_id_no' => $encrypted_data,
            'pag_ibig_id_no' => $encrypted_data,
            'philhealth_id_no' => $encrypted_data,
            'sss_id_no' => $encrypted_data,
            'prc_id_no' => $encrypted_data,
            'tin_id_no' => $encrypted_data,
            'rdo_no' => $encrypted_data,
            'bank_account_no' => $encrypted_data,
            'personal_information_id' => $personal_information->id,
        ]);

        EducationalBackground::create([
            'level' => 'Elementary',
            'name' => 'San roque Elementary School',
            'year_graduated' => Carbon::createFromFormat('Y-m-d', "2010-7-15"),
            'personal_information_id' => $personal_information->id,
        ]);

        OtherInformation::create([
            'title' => 'Watching Movie',
            'skills_hobbies' => TRUE,
            'personal_information_id' => $personal_information->id,
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(1)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(2)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(3)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(4)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(5)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(6)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(7)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(8)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(9)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(10)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(11)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(12)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(13)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        LegalInformation::create([
            'legal_iq_id' => LegalInformationQuestion::find(14)->id,
            'answer' => TRUE,
            'personal_information_id' => $personal_information->id
        ]);

        $password = 'Zcmc_Umis2023@';
        $hashPassword = Hash::make($password.env('SALT_VALUE'));
        $encryptedPassword = Crypt::encryptString($hashPassword);
        
        $now = Carbon::now();
        $fortyDaysFromNow = $now->addDays(40);
        $fortyDaysExpiration = $fortyDaysFromNow->toDateTimeString();

        $employee_profile = EmployeeProfile::create([
            'employee_id' => '2022091351',
            'date_hired' => Carbon::createFromFormat('Y-m-d', "2022-9-13"),
            'password_encrypted' => $encryptedPassword,
            'password_created_at' => now(),
            'password_expiration_at' => $fortyDaysExpiration,
            'biometric_id' => 5335,
            'allow_time_adjustment' => TRUE,
            'employment_type_id' => EmploymentType::find(3)->id,
            'personal_information_id' => $personal_information->id
        ]);

        IssuanceInformation::create([
            'employee_profile_id' => $employee_profile->id,
            'license_no' => '123456',
            'govt_issued_id' => '987654321',
            'ctc_issued_date' => now(),
            'ctc_issued_at' => 'ZCMC',
            'person_administrative_oath' => null
        ]);

        AssignArea::create([
            'employee_profile_id' => $employee_profile->id,
            'section_id' => Section::where('code', 'MMS')->first()->id,
            'designation_id' => Designation::where('code', 'CP III')->first()->id,
            'effective_at' => now()
        ]);
    }

    protected function encryptData($dataToEncrypt)
    {
        return openssl_encrypt($dataToEncrypt, env("ENCRYPT_DECRYPT_ALGORITHM"), env("DATA_KEY_ENCRYPTION"), 0, substr(md5(env("DATA_KEY_ENCRYPTION")), 0, 16));
    }
}
