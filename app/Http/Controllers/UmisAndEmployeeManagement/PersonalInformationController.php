<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\RequestLogger;
use App\Services\FileValidationAndUpload;
use App\Http\Requests\PersonalInformationRequest;
use App\Http\Resources\PersonalInformationResource;
use App\Models\PersonalInformation;

class PersonalInformationController extends Controller
{
    private $CONTROLLER_NAME = 'Personal Information';
    private $PLURAL_MODULE_NAME = 'personal informations';
    private $SINGULAR_MODULE_NAME = 'personal information';

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
            $personal_informations = PersonalInformation::all();

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');
            
            return response()->json(['data' => PersonalInformationResource::collection($personal_informations)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
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

            $residential = Address::create([
                'address' => $request->input('r_address'),
                'telephone' => $request->input('r_telephone'),
                'zip_code' => $request->input('r_zip_code'),
                'type' => 'residential',
                'personal_information_id' => $personal_information->id
            ]);

            $permanent = Address::create([
                'address' => $request->input('r_address'),
                'telephone' => $request->input('r_telephone'),
                'zip_code' => $request->input('r_zip_code'),
                'type' => 'permanent',
                'personal_information_id' => $personal_information->id
            ]);

            $data = [
                'personal_information' => $personal_information,
                'residential' => $residential,
                'permanent' => $permanent
            ];
            
            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json($data, Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new PersonalInformationResource($personal_information)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, PersonalInformationRequest $request)
    {
        try{
            $personal_information = PersonalInformation::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $personal_information->update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => new PersonalInformationResource($personal_information),'message' => 'Employee PDS updated.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $personal_information = PersonalInformation::findOrFail($id);

            if(!$personal_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
