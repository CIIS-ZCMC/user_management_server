<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PersonalInformationUpdateRequest;
use App\Models\Address;
use App\Http\Requests\PasswordApprovalRequest;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Services\FileValidationAndUpload;
use App\Http\Requests\PersonalInformationRequest;
use App\Http\Resources\PersonalInformationResource;
use App\Models\PersonalInformation;

class PersonalInformationController extends Controller
{
    private $CONTROLLER_NAME = 'Personal Information';
    private $PLURAL_MODULE_NAME = 'personal informations';
    private $SINGULAR_MODULE_NAME = 'personal information';

    public function index(Request $request)
    {
        try{
            $personal_informations = PersonalInformation::all();
            
            return response()->json(['data' => PersonalInformationResource::collection($personal_informations)], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Employee PDS Registration
     * This must have registration of employee information such as name, height, weight, etc
     * contacts and addresses
     */
    public function store(PersonalInformationRequest $request)
    {
        try{
            $is_res_per = $request->is_res_per === 1 ? true:false;
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $personal_information = PersonalInformation::create($cleanData);

            $residential_address = [
                'address' => strip_tags($request->residential_address),
                'zip_code' => strip_tags($request->residential_zip_code),
                'telephone_no' => strip_tags($request->residential_telephone),
                'is_res_per' => $request->is_res_per,
                'is_residential' => 1,
                'personal_information_id' => $personal_information->id
            ];

            $residential = Address::create($residential_address);

            if($is_res_per !== null && $is_res_per){
                $data = [
                    'personal_information' => $personal_information,
                    'residential' => $residential,
                    'permanent' => $residential
                ];

                return response()->json($data, Response::HTTP_OK);
            }

            $permanent_address =  [
                'address' => strip_tags($request->permanent_address),
                'telephone_no' => strip_tags($request->permanent_telephone),
                'zip_code' => strip_tags($request->permanent_zip_code),
                'is_res_per' => 0,
                'is_residential' => 0,
                'personal_information_id' => $personal_information->id
            ];

            $permanent = Address::create($permanent_address);

            $data = [
                'personal_information_id' => $personal_information->id,
                'personal_information' => $personal_information,
                'residential' => $residential,
                'permanent' => $permanent
            ];
            
            return $personal_information;
        }catch(\Throwable $th){
            throw new \Exception("Failed to register personal information.", 400);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $personal_information = PersonalInformation::findOrFail($id);

            if(!$personal_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new PersonalInformationResource($personal_information)], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, PersonalInformationUpdateRequest $request)
    {
        try{
            $personal_information = PersonalInformation::find($id);

            $is_res_per = $request->is_res_per === 1 ? true:false;
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $personal_information->update($cleanData);

            $residential_address = [
                'res_id' => strip_tags($request->res_id),
                'address' => strip_tags($request->r_address),
                'zip_code' => strip_tags($request->r_zip_code),
                'telephone_no' => strip_tags($request->r_telephone),
                'is_res_per' => $request->is_res_per,
                'is_residential' => 1,
                'personal_information_id' => $personal_information->id
            ];
            
            if(($residential_address['res_id'] ===  null || $residential_address['res_id'] === 'null') && $residential_address['address'] !== null){
                Address::create($residential_address);
            }

            if(!$residential_address['is_res_per']){
                $permanent_address =  [
                    'address' => strip_tags($request->p_address),
                    'telephone_no' => strip_tags($request->p_telephone),
                    'zip_code' => strip_tags($request->p_zip_code),
                    'is_res_per' => 0,
                    'is_residential' => 0,
                    'personal_information_id' => $personal_information->id
                ];

                Address::create($permanent_address);
            }

            if($residential_address['is_res_per']){
                $per_address = Address::where('is_residential', 0)->where('personal_information_id', $personal_information->id)->first();
                if($per_address){
                    $per_address->delete();
                }
            }
            
            return $personal_information;
        }catch(\Throwable $th){
            throw new \Exception("Failed to register employee family background.", 400);
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

            $personal_information = PersonalInformation::findOrFail($id);

            if(!$personal_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function generatePDS(Request $request)
    {
        try{

            // // return view('pds.pdsForm');
            // $options = new Options();
            // $options->set('isPhpEnabled', true);
            // $options->set('isHtml5ParserEnabled', true);
            // $options->set('isRemoteEnabled', true);
            // $dompdf = new Dompdf($options);
            // $dompdf->getOptions()->setChroot([base_path() . '\public\storage']);
            // $dompdf->loadHtml(view('pds.pdsForm',  []));
            // $dompdf->setBasePath(public_path());
            // $dompdf->setPaper('Legal', 'portrait');
            // $dompdf->render();
            // $filename = 'Personal Data Sheet.pdf';

            $options = new Options();
            $options->set('isPhpEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->getOptions()->setChroot([base_path() . '/public/storage']);
            $html = view('pds.pdsForm', [])->render();
            $dompdf->loadHtml($html);

            $dompdf->setPaper('Legal', 'portrait');
            $dompdf->render();
            $filename = 'PDS.pdf';


            /* Downloads as PDF */
            $dompdf->stream($filename); 
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'generatePDS', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}