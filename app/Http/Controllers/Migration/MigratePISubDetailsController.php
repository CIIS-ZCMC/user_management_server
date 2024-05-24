<?php

namespace App\Http\Controllers\migration;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Child;
use App\Models\Contact;
use App\Models\EmployeeProfile;
use App\Models\FamilyBackground;
use App\Models\IdentificationNumber;
use App\Models\PersonalInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

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
