<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\RequestLogger;
use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\EmployeeProfile;

class ContactController extends Controller
{
    private $CONTROLLER_NAME = 'Contact';
    private $PLURAL_MODULE_NAME = 'contacts';
    private $SINGULAR_MODULE_NAME = 'contact';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $contact = Contact::where('personal_information_id', $id)->first();

            if(!$contact)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $contact['id'], true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new ContactResource($contact),
                'message' => 'Contact details retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
           $this->requestLogger->errorLog($this->CONTROLLER_NAME,'employeeContact', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function findByEmployeeID($id, Request $request)
    {
        try{
            $contact = EmployeeProfile::find($id);

            if(!$contact)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $contact['id'], true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new ContactResource($contact),
                'message' => 'Contact detail retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
           $this->requestLogger->errorLog($this->CONTROLLER_NAME,'employeeContact', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(ContactRequest $request)
    {
        try{ 
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $contact = Contact::create($cleanData);

            $this->requestLogger->registerSystemLogs($request, $contact['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new ContactResource($contact),
                'message' => 'New Employee contact added.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
           $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{  
            $contact = Contact::find($id);

            if(!$contact)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new ContactResource($contact)], Response::HTTP_OK);
        }catch(\Throwable $th){
           $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, Request $request)
    {
        try{ 
            $contact = Contact::where("personal_information_id", $id)->first();

            if(!$contact)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $contact -> update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new ContactResource($contact),
                'message' => 'Employee contact details updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
           $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $contact = Contact::findOrFail($id);

            if(!$contact)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $contact -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Employee contact record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
           $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByPersonalInformation($id, Request $request)
    {
        try{
            $contact = Contact::where("personal_information_id", $id)->first();

            if(!$contact)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $contact -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Employee contact record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
           $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByEmployeeID($id, Request $request)
    {
        try{
            $employee = EmployeeProfile::find($id);

            if(!$employee)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee->personalInformation;
            
            $contact = $personal_information->contact;
            $contact -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Employee contact record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
           $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
