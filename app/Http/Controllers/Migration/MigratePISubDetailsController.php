<?php

namespace App\Http\Controllers\migration;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Child;
use App\Models\CivilServiceEligibility;
use App\Models\Contact;
use App\Models\EducationalBackground;
use App\Models\EmployeeProfile;
use App\Models\FamilyBackground;
use App\Models\IdentificationNumber;
use App\Models\IssuanceInformation;
use App\Models\LegalInformation;
use App\Models\LegalInformationQuestion;
use App\Models\PersonalInformation;
use App\Models\Reference;
use App\Models\SpecialAccessRole;
use App\Models\SystemRole;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Carbon\Carbon;

class MigratePISubDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        try {
            // For migrating the personal information
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Truncate the table
            // DB::table('contacts')->truncate();
            // DB::table('addresses')->truncate();
            // DB::table('childs')->truncate();
            // DB::table('identification_numbers')->truncate();
            DB::table('family_backgrounds')->truncate();
            DB::table('civil_service_eligibilities')->truncate();
            DB::table('educational_backgrounds')->truncate();
            DB::table('legal_information_questions')->truncate();
            DB::table('legal_informations')->truncate();
            DB::table('references')->truncate();
            DB::table('trainings')->truncate();
            // Re-enable foreign key checks







            // DB::table('employee_profiles')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::beginTransaction();


            $filePath = storage_path('../app/json_data/contacts.csv');



            // Create a CSV reader
            $reader = Reader::createFromPath($filePath, 'r');
            $reader->setHeaderOffset(0); // Assumes first row is header

            // Read the CSV data
            $csvData = $reader->getRecords();
            $temp = null;

            // ======\
            //========> Importing Contacts
            // ======/
            // Personalinformation right join contacts.csv
            // foreach ($csvData as $index => $row) {

            //     if (!$row['email address'] == '' || !$row['email address'] === null) {
            //         //Get the personalID
            //         $personalId = EmployeeProfile::select('pi.*')->leftJoin('personal_informations as pi', 'employee_profiles.personal_information_id', 'pi.id')
            //             ->where('employee_profiles.employee_id', $row['id'])
            //             ->first()->id;
            //         // dd($personalId->id);
            //         //Create Contact
            //         $contact = new Contact();
            //         $contact->phone_number = $row['contact number'];
            //         $contact->email_address = $row['email address'];
            //         $contact->personal_information_id = $personalId;
            //         $contact->save();
            //     } else {
            //         $personalId = EmployeeProfile::select('pi.*')->leftJoin('personal_informations as pi', 'employee_profiles.personal_information_id', 'pi.id')
            //             ->where('employee_profiles.employee_id', $row['id'])
            //             ->first()->id;
            //         $contact = new Contact();
            //         $contact->phone_number = null;
            //         $contact->email_address = 'gg@noemail.com';
            //         $contact->personal_information_id = $personalId;
            //         $contact->save();
            //     }
            // }

            $employee = EmployeeProfile::all();

            foreach ($employee as $emp) {
                $piId = $emp['personal_information_id'];
                $employee_no = $emp['employee_id'];
                // dd($employee_no);
                // ======\
                //========> Migrating Address
                // ======/
                // $Address =  DB::connection('sqlsrv')->select("SELECT e.employeeid ,e.no ,pd.*
                //         FROM employee e 
                //         left join employeedetail pd 
                //         on e.employeeid = pd.employeeid 
                //         where no = '$employee_no'");
                // if ($Address != null || !count($Address) < 1) {
                //     $ress = $Address[0]->residentaddress;
                //     $permadd = $Address[0]->permanentaddress;
                //     $zipCode = $Address[0]->zipcode;
                //     $isResAndPerma = $Address[0]->residentaddress == $Address[0]->permanentaddress ? 1 : 0;
                //     $cAddr = new Address();
                //     $cAddr->address = $ress;
                //     $cAddr->zip_code = $zipCode;
                //     $cAddr->is_residential_and_permanent = $isResAndPerma;
                //     $cAddr->is_residential = $isResAndPerma == 1 ? 0 : 1;
                //     $cAddr->personal_information_id = $piId;
                //     $cAddr->save();
                // }
                // // ======\
                // //========> Migrating Children
                // // ======/
                // $children =  DB::connection('sqlsrv')->select("SELECT e.employeeid ,e.no ,pd.employeedetailid, child.* 
                // from children child left join employeedetail pd
                // on child.employeedetailid = pd.employeedetailid left join employee e 
                // on pd.employeedetailid = e.employeeid 
                // where e.no = '$employee_no'");
                // if (count($children) > 0) {
                //     foreach ($children as $val) {
                //         $child = new Child();
                //         $child->personal_information_id = $piId;
                //         $child->last_name = $val->lastname;
                //         $child->first_name = $val->firstname;
                //         $child->middle_name = $val->mi;
                //         $child->gender = $val->gender == 1 ? 'Male' : 'Female';
                //         $child->birthdate = $val->birthdate;
                //         $child->save();
                //     }
                // }

                // // ======\
                // //========> Migrating Identifications
                // // ======/
                // $IDno =  DB::connection('sqlsrv')->select("SELECT e.employeeid,e.bankaccountno ,e.no ,pd.*
                //         FROM employee e 
                //         left join employeedetail pd 
                //         on e.employeeid = pd.employeeid 
                //         where no = '$employee_no'");
                // // dd($IDno[0]->gsisno);
                // $identification = new IdentificationNumber();
                // $identification->gsis_id_no = $IDno[0]->gsisno == '' || $IDno[0]->gsisno == null ? Crypt::encrypt('N/A') : Crypt::encrypt($IDno[0]->gsisno);
                // $identification->pag_ibig_id_no = $IDno[0]->pagibigcode == '' || $IDno[0]->pagibigcode == null ? Crypt::encrypt('N/A') : Crypt::encrypt($IDno[0]->pagibigcode);
                // $identification->philhealth_id_no = $IDno[0]->philhealthno == '' || $IDno[0]->philhealthno == null ? Crypt::encrypt('N/A') : Crypt::encrypt($IDno[0]->philhealthno);
                // $identification->sss_id_no = $IDno[0]->sssno == '' || $IDno[0]->sssno == null ? Crypt::encrypt('N/A') : Crypt::encrypt($IDno[0]->sssno);
                // $identification->prc_id_no = $IDno[0]->prcid == '' || $IDno[0]->prcid == null ? Crypt::encrypt('N/A') : Crypt::encrypt($IDno[0]->prcid);
                // $identification->tin_id_no = $IDno[0]->tinno == '' || $IDno[0]->tinno == null ? Crypt::encrypt('N/A') : Crypt::encrypt($IDno[0]->tinno);
                // $identification->rdo_no = $IDno[0]->rdono == '' || $IDno[0]->rdono == null ? Crypt::encrypt('N/A') : Crypt::encrypt($IDno[0]->rdono);
                // $identification->bank_account_no = $IDno[0]->bankaccountno == '' || $IDno[0]->bankaccountno == null ? Crypt::encrypt('N/A') : Crypt::encrypt($IDno[0]->bankaccountno);
                // $identification->personal_information_id = $piId;
                // $identification->save();

                //Temporary Background
                FamilyBackground::create([
                    'spouse' => null,
                    'address' => null,
                    'zip_code' => null,
                    'date_of_birth' => null,
                    'occupation' => null,
                    'employer' => null,
                    'business_address' => null,
                    'telephone_no' => null,
                    'tin_no' => null,
                    'rdo_no' => null,
                    'father_first_name' => null,
                    'father_middle_name' => null,
                    'father_last_name' => null,
                    'father_ext_name' => null,
                    'mother_first_name' => null,
                    'mother_middle_name' => null,
                    'mother_last_name' => null,
                    'mother_maiden_name' => null,
                    'personal_information_id' => $piId
                ]);
                CivilServiceEligibility::create([
                    'career_service' => 'cse',
                    'rating' => 80,
                    'date_of_examination' => Carbon::now(),
                    'place_of_examination' => 'zc',
                    'license_number' => 123456,
                    'license_release_at' => Carbon::now(),
                    'personal_information_id' => $piId,
                    'is_request' => 0,
                    'approved_at' =>  Carbon::now(),
                    'attachment' => null
                ]);

                EducationalBackground::create([
                    'personal_information_id' => $piId,
                    'level' => 1,
                    'name' => 'elem',
                    'degree_course' => 'it',
                    'year_graduated' =>  Carbon::now(),
                    'highest_grade' => 90,
                    'inclusive_from' =>  Carbon::now(),
                    'inclusive_to' =>  Carbon::now(),
                    'academic_honors' => 'samp',
                    'attachment' => null,
                    'is_request' => 0,
                    'approved_at' =>  Carbon::now(),
                    'attachment' => null,
                ]);

                LegalInformationQuestion::create(
                    [
                        'order_by' => 1,
                        'content_question' => 'Have you ever been found guilty by any administrative offense?',
                        'has_detail' => 1,
                        'has_yes_no' => 1,
                        'has_date' => 0,
                        'has_sub_question' => 0,
                        'legal_iq_id' => null
                    ]
                );
                LegalInformation::create([
                    'legal_iq_id' => null,
                    'personal_information_id' =>  $piId,
                    'answer' => 1,
                    'details' => 'sample',
                    'date' => Carbon::now()
                ]);

                Reference::create([
                    'name' => 'jose',
                    'address' => 'gusu',
                    'contact_no' => '299842',
                    'personal_information_id' => $piId
                ]);
                Training::create([
                    'title' => 'sample',
                    'inclusive_from' => Carbon::now(),
                    'inclusive_to' => Carbon::now(),
                    'hours' => 2,
                    'type_of_ld' => 'sampleidtype',
                    'conducted_by' => 'dict',
                    'total' => 2,
                    'personal_information_id' => $piId,
                    'attachment' => null,
                    'is_request' => 0,
                    'approved_at' => Carbon::now(),
                    'attachment' => null
                ]);
                IssuanceInformation::create([
                    'license_no' => 1313,
                    'govt_issued_id' => 2323,
                    'ctc_issued_date' => Carbon::now(),
                    'ctc_issued_at' => 'zc',
                    'person_administrative_oath' => 'sample',
                    'employee_profile_id' => $piId
                ]);

                $system_role =  SystemRole::find(9);

                $employee_profile = EmployeeProfile::where(
                    'employee_id',
                    $employee_no
                )->first();
                SpecialAccessRole::create([
                    'employee_profile_id' => $employee_profile->id,
                    'system_role_id' => $system_role->id,
                ]);
            }


            DB::commit();
            return response()->json('Employee Contact successfully Import');
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
