<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Services\RequestLogger;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\EmploymentTypeResource;
use App\Models\EmploymentType;

class EmploymentTypeController extends Controller
{  
    private $CONTROLLER_NAME = 'Employment Type';
    private $PLURAL_MODULE_NAME = 'employment_types';
    private $SINGULAR_MODULE_NAME = 'employment_type';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    public function index(Request $request)
    {
        try{
            $employment_types = EmploymentType::all();

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');
            
            return response()->json([
                'data' => EmploymentTypeResource::collection($employment_types),
                'message' => 'Employment type list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(Request $request)
    {
        try{
            $request->validate([
                'name' => 'required|string|max:255'
            ]);

            $name = strip_tags($request->input('name'));

            $employment_type = EmploymentType::create($name);

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new EmploymentTypeResource($employment_type),
                'message' => 'New employment type registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $employment_type = EmploymentType::find($id);

            if(!$employment_type)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => new EmploymentTypeResource($employment_type), 'message' => 'Employment Type record retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, Request $request)
    {
        try{
            $request->validate([
                'name' => 'required|string|max:255'
            ]);

            $employment_type = EmploymentType::find($id);

            if(!$employment_type)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $name = strip_tags($request->input('name'));

            $employment_type -> update(['name' => $name]);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new EmploymentTypeResource($employment_type),
                'message' => 'Employment type record updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{ 
            $password = strip_tags($request->input('password'));

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $employment_type = EmploymentType::findOrFail($id);

            if(!$employment_type)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            if(count($employment_type->employees)>0){
                return response()->json(['message' => 'Some data is using this employment type record deletion is prohibited.'], Response::HTTP_BAD_REQUEST);
            }

            $employment_type -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employment Type record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}