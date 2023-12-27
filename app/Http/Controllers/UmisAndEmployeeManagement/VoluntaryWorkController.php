<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\PasswordApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Services\RequestLogger;
use App\Http\Requests\VoluntaryWorkRequest;
use App\Http\Resources\VoluntaryWorkResource;
use App\Models\VoluntaryWork;
use App\Models\EmployeeProfile;

class VoluntaryWorkController extends Controller
{
    private $CONTROLLER_NAME = 'VoluntaryWork';
    private $PLURAL_MODULE_NAME = 'voluntary_works';
    private $SINGULAR_MODULE_NAME = 'voluntary_work';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $voluntary_works = VoluntaryWork::where('personal_information_id', $id)->get();

            if(!$voluntary_works)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $voluntary_works['id'], true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => VoluntaryWorkResource::collection($voluntary_works), 
                'message' => 'Employee voluntary work record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID(Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::where('employee_id',$request->input('employee_id'))->get();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $personal_information = $employee_profile->personalInformation;
            $voluntary_works = $personal_information->voluntary_works;

            $this->requestLogger->registerSystemLogs($request, $voluntary_works['id'], true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => VoluntaryWorkResource::collection($voluntary_works), 
                'message' => 'Employee voluntary work record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(VoluntaryWorkRequest $request)
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

            $this->requestLogger->registerSystemLogs($request, $voluntary_work['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new VoluntaryWorkResource($voluntary_work),
                'message' => 'New employee voluntary work registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function storeMany(Request $request)
    {
        try{
            $success = [];
            $failed = [];
            $cleanData = [];

            foreach($request->voluntary_works as $voluntary_work){
                foreach ($voluntary_work as $key => $value) {
                    if ($value === null) {
                        $cleanData[$key] = $value;
                        continue;
                    }
                }
                $cleanData[$key] = strip_tags($value);
                $voluntary_work = VoluntaryWork::create($cleanData);

                if(!$voluntary_work){
                    $failed[] = $cleanData;
                    continue;
                }

                $success = $voluntary_work;
            };

            $voluntary_work = VoluntaryWork::create($cleanData);

            $this->requestLogger->registerSystemLogs($request, $voluntary_work['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            if(count($failed) > 0){
                return response()->json([
                    'data' => VoluntaryWorkResource::collection($success),
                    'failed' => $failed,
                    'message' => 'Some data failed to registere.'
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => VoluntaryWorkResource::collection($success),
                'message' => 'New employee voluntary work registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $voluntary_work = VoluntaryWork::findOrFail($id);

            if(!$voluntary_work)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new VoluntaryWork($voluntary_work), 
                'message' => 'Voluntary work record found.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, VoluntaryWorkRequest $request)
    {
        try{
            $voluntary_work = VoluntaryWork::find($id);

            if(!$voluntary_work)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $voluntary_work->update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new VoluntaryWorkResource($voluntary_work), 
                'message' => 'Employee voluntary work data is updated.'
            ], Response::HTTP_OK);
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

            $voluntary_work = VoluntaryWork::findOrFail($id);

            if(!$voluntary_work)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $voluntary_work->delete();
            
            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee voluntary work record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByPersonalInformationID($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $voluntary_works = VoluntaryWork::where('personal_information_id', $id)->get();

            if(!$voluntary_works)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($voluntary_works as $key => $voluntary_work){
                $voluntary_work->delete();
            }
            
            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee voluntary works record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByEmployeeID(PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::where('employee_id',$request->input('employee_id'))->get();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee_profile->personalInformation;
            $voluntary_works = $personal_information->voluntary_works;

            foreach($voluntary_works as $key => $voluntary_work){
                $voluntary_work->delete();
            }
            
            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee voluntary works record deleted'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
