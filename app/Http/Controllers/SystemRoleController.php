<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SystemRole;

class SystemRoleController extends Controller
{   public function index(Request $request)
    {
        try{
            $data = SystemRole::all();

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("SystemRole Controller[index] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    
    public function store(Request $request)
    {
        try{  
            $validator = Validator::make($request->all(), [
                'FK_role_ID' => 'required|string|max:255',
                'FK_system_ID' => 'required|string|max:255',
                'abilities' => 'required|array'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $data = [
                'FK_role_ID' => $request->input('FK_role_ID'),
                'FK_system_ID' => $request->input('FK_system_ID'),
                'abilities' => $request->input('abilities')
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }


            $data = SystemRole::all();
            $data -> FK_role_ID = $cleanData['FK_role_ID'];
            $data -> FK_system_ID = $cleanData['FK_system_ID'];
            $data -> abilities = $cleanData['abilities'];
            $data -> created_at = now();
            $data -> updated_at = now();
            $data -> save();

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("SystemRole Controller[store] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function show($id, Request $request)
    {
        try{
            $data = SystemRole::find($id);

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("SystemRole Controller[show] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function update($id, Request $request)
    {
        try{
            $data = System::find($id);

            $validator = Validator::make($request->all(), [
                'FK_role_ID' => 'required|string|max:255',
                'FK_system_ID' => 'required|string|max:255',
                'abilities' => 'required|array'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $data = [
                'FK_role_ID' => $request->input('FK_role_ID'),
                'FK_system_ID' => $request->input('FK_system_ID'),
                'abilities' => $request->input('abilities')
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $data -> FK_role_ID = $cleanData['FK_role_ID'];
            $data -> FK_system_ID = $cleanData['FK_system_ID'];
            $data -> abilities = $cleanData['abilities'];
            $data -> created_at = now();
            $data -> updated_at = now();
            $data -> save();
            
            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("SystemRole Controller[update] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function destroy($id, Request $request)
    {
        try{
            $data = SystemRole::findOrFail($id);
            $data -> deleted = TRUE;
            $data -> updated_at = now();
            $data -> save();

            return response() -> json(['data' => 'Success'], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("SystemRole Controller[destroy] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
}
