<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Profile;

use App\Http\Requests\ProfileRequest;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        try{
            $data = Profile::all();

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            $this->logs('index', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    public function store(ProfileRequest $request)
    {
        try{
            $data = [
                'first_name' => $request->input('first_name'),
                'middle_name' => $request->input('middle_name'),
                'last_name' => $request->input('last_name'),
                'extension_name' => $request->input('extension_name'),
                'dob' => $request->input('dob'),
                'sex' => $request->input('sex'),
                'contact' => $request->input('contact'),
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $data = Profile::create([$cleanData]);

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            $this->logs('store', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function show($id,Request $request)
    {
        try{
            $data = Profile::find($id);

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            $this->logs('show', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function update($id, ProfileRequest $request)
    {
        try{
            $data = Profile::find($id);

            if(!$data)
            {
                return response() -> json(['message' => "No record found."], 404);
            }
            
            $data = [
                'first_name' => $request->input('first_name'),
                'middle_name' => $request->input('middle_name'),
                'last_name' => $request->input('last_name'),
                'extension_name' => $request->input('extension_name'),
                'dob' => $request->input('dob'),
                'sex' => $request->input('sex'),
                'contact' => $request->input('contact'),
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $data -> update([$cleanData]);

            return response() -> json(['data' => 'Success'], 200);
        }catch(\Throwable $th){
            $this->logs('update', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    public function destroy($id, Request $request)
    {
        try{
            $data = Profile::find($id);
            $data -> delete();

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            $this->logs('destroy', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    protected function logs($action, $errorMessage)
    {
        Log::channel('custom-error') -> error("Profile Controller[".$action."] :".$errorMessage);    
    }
}
