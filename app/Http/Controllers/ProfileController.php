<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Profile;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        try{
            $data = Profile::all();

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("Profile Controller[index] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'middle_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'extension_name' => 'nullable|string|max:255',
                'dob' => 'required|date',
                'sex' => 'required|string|max:255',
                'contact' => 'nullable|string|max:255',
                'image_url' => 'required|string|max:255',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $data = [
                'first_name' => $request->input('first_name'),
                'middle_name' => $request->input('middle_name'),
                'last_name' => $request->input('last_name'),
                'extension_name' => $request->input('extension_name'),
                'dob' => $request->input('dob'),
                'sex' => $request->input('sex'),
                'contact' => $request->input('contact'),
                'image_url' => $request->input('image_url'),
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }


            $data = new Profile;
            $data -> first_name = $cleanData['first_name'];
            $data -> middle_name = $cleanData['middle_name'];
            $data -> last_name = $cleanData['last_name'];
            $data -> extension_name = $cleanData['extension_name'];
            $data -> dob = $cleanData['dob'];
            $data -> sex = $cleanData['sex'];
            $data -> contact = $cleanData['contact'];
            $data -> image_url = $cleanData['contact'];
            $data -> created_at = now();
            $data -> updated_at = now();
            $data -> save();


            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("Profile Controller[store] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function show($id,Request $request)
    {
        try{
            $data = Profile::find($id);

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("Profile Controller[show] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function update($id, Request $request)
    {
        try{
            $data = Profile::find($id);

            if(!$data)
            {
                return response() -> json(['message' => "No record found."], 404);
            }

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'middle_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'extension_name' => 'nullable|string|max:255',
                'dob' => 'required|date',
                'sex' => 'required|string|max:255',
                'contact' => 'nullable|string|max:255',
                'image_url' => 'required|string|max:255',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $data = [
                'first_name' => $request->input('first_name'),
                'middle_name' => $request->input('middle_name'),
                'last_name' => $request->input('last_name'),
                'extension_name' => $request->input('extension_name'),
                'dob' => $request->input('dob'),
                'sex' => $request->input('sex'),
                'contact' => $request->input('contact'),
                'image_url' => $request->input('image_url'),
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $data -> first_name = $cleanData['first_name'];
            $data -> middle_name = $cleanData['middle_name'];
            $data -> last_name = $cleanData['last_name'];
            $data -> extension_name = $cleanData['extension_name'];
            $data -> dob = $cleanData['dob'];
            $data -> sex = $cleanData['sex'];
            $data -> contact = $cleanData['contact'];
            $data -> image_url = $cleanData['contact'];
            $data -> updated_at = now();
            $data -> save();

            return response() -> json(['data' => 'Success'], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("Profile Controller[update] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    public function destroy($id, Request $request)
    {
        try{
            $data = Profile::find($id);
            $data -> deleted = TRUE;
            $data -> updated_at = now();
            $data -> save();

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("Profile Controller[destroy] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
}
