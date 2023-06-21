<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\System;

class SystemController extends Controller
{   public function index(Request $request)
    {
        try{
            $data = System::all();

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("System Controller[index] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    
    public function store(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'domain' => 'required|string|max:255'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $data = [
                'name' => $request->input('name'),
                'domain' => $request->input('domain')
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $data = System::all();
            $data -> name = $cleanData['name'];
            $data -> domain = $cleanData['domain'];
            $data -> created_at = now();
            $data -> updated_at = now();
            $data -> save();

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("System Controller[store] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    public function show($id, Request $request)
    {
        try{
            $data = System::find($id);

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("System Controller[show] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function update($id, Request $request)
    {
        try{
            $data = System::find($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'domain' => 'required|string|max:255'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $data = [
                'name' => $request->input('name'),
                'domain' => $request->input('domain')
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $data -> name = $cleanData['name'];
            $data -> domain = $cleanData['domain'];
            $data -> created_at = now();
            $data -> updated_at = now();
            $data -> save();

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("System Controller[update] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function destroy($id, Request $request)
    {
        try{
            $data = System::findOrFail($id);
            $data -> deleted = TRUE;
            $data -> updated_at = now();
            $data -> save();

            return response() -> json(['data' => 'Success'], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("System Controller[destroy] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
}
