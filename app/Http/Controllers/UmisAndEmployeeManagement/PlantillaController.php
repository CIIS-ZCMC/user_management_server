<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Services\RequestLogger;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\PlantillaRequest;
use App\Http\Resources\DesignationEmployeesResource;
use App\Http\Resources\PlantillaResource;
use App\Models\Plantilla;
use App\Models\PlantillaNumber;
use App\Models\PlantillaRequirement;

class PlantillaController extends Controller
{
    private $CONTROLLER_NAME = 'Plantilla';
    private $PLURAL_MODULE_NAME = 'plantillas';
    private $SINGULAR_MODULE_NAME = 'plantilla';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $plantillas = Cache::remember('plantillas', $cacheExpiration, function(){
                return Plantilla::all();
            });

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');
            
            return response()->json([
                'data' => PlantillaResource::collection($plantillas),
                'message' => 'Plantilla list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByDesignationID($id, Request $request)
    {
        try{
            $sector_employees = Plantilla::with('assigned_areas')->findOrFail($id);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => DesignationEmployeesResource::collection($sector_employees),
                'message' => 'Plantilla record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'employeesOfSector', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(PlantillaRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'plantilla_number'){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $plantilla = Plantilla::create($cleanData);

            $cleanData['plantilla_id'] = $plantilla->id;

            $failed = [];

            foreach($cleanData['plantilla_number'] as $value)
            {
                try{
                    if(!is_string($value))
                    {
                        $failed_to_register = [
                            'plantilla_number' => $value,
                            'remarks' => 'Invalid type require string.'
                        ];
                        
                        $failed[] = $failed_to_register;
                        continue;
                    }

                    PlantillaNumber::create([
                        'number' => $value,
                        'plantilla_id' => $plantilla->id
                    ]);
                }catch(\Throwable $th){
                    $failed_to_register = [
                        'plantilla_number' => $value,
                        'remarks' => 'Something went wrong.'
                    ];
                    $failed[] = $failed_to_register;
                    continue;
                }
            }

            $data = new PlantillaResource($plantilla);
            $message = 'Plantilla created successfully.';

            if(count($failed) === count($cleanData['plantilla_number']))
            {
                $data = [];
                $message = 'Failed to register plantilla numbers.';
            }

            if(count($failed) > 0)
            {
                $data = [
                    'new_plantilla' => new PlantillaResource($plantilla),
                    'failed' => $failed
                ];
                $message = 'Some plantilla number failed to register.';
            }

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([$data, $message], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $plantilla = Plantilla::find($id);

            if(!$plantilla)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new PlantillaResource($plantilla),
                'message' => 'Plantilla record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, PlantillaRequest $request)
    {
        try{
            $plantilla = Plantilla::find($id);

            if(!$plantilla)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'plantilla_number'){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $plantilla -> update($cleanData);
            $plantilla_requirement = $plantilla->requirement;
            $plantilla_requirement->update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new PlantillaResource($plantilla),
                'message' => 'Plantilla update successfully.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

            $plantilla = Plantilla::findOrFail($id);

            if(!$plantilla)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $plantilla_numbers = $plantilla->plantillaNumbers;

            $deletion_prohibited = false;

            foreach($plantilla_numbers as $plantilla_number)
            {
                if($plantilla_number->employee_profile_id !== null)
                {
                    $deletion_prohibited = true;
                    break;
                }
            }

            if($deletion_prohibited)
            {
                return response()->json(['message' => "Some plantilla number are already in used deletion prohibited."], Response::HTTP_BAD_REQUEST);
            }


            foreach($plantilla_numbers as $plantilla_number)
            { 
                $plantilla_number->delete();
            }


            $requirement = $plantilla->requirement;
            $requirement->delete();
            $plantilla -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Plantilla record and plantilla number are deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyPlantillaNumber($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $plantilla_number = PlantillaNumber::findOrFail($id);

            if(!$plantilla_number)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $plantilla_number -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Plantilla number deleted successfully.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
