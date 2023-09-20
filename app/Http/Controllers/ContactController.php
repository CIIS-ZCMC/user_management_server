<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $contacts = Cache::remember('contacts', $cacheExpiration, function(){
                return Contact::all();
            });

            return response()->json(['data' => ContactResource::collection($contacts)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function employeeContact($id, Request $request)
    {
        try{
            $contact = Contact::where('personal_information_id', $id)->first();

            return response()->json(['data' => new ContactResource($contact)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(ContactRequest $request)
    {
        try{ 
            $cleanData = [];

            $cleanData['uuid'] = Str::uuid();
            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $contact = Contact::create($cleanData);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('store', $th->getMessage());
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

            return response()->json(['data' => new ContactResource($contact)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
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

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $contact = Contact::where("personal_information_id", $id)->first();

            if(!$contact)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $contact -> delete();

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function infoLog($module, $message)
    {
        Log::channel('custom-info')->info('Personal Information Controller ['.$module.']: message: '.$errorMessage);
    }

    protected function errorLog($module, $errorMessage)
    {
        Log::channel('custom-error')->error('Personal Information Controller ['.$module.']: message: '.$errorMessage);
    }
}
