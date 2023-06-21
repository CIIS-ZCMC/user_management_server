<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\UserSystemRole;

class UserSystemRoleController extends Controller
{   public function index(Request $request)
    {
        try{
            $data = UserSystemRole::all();

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("UserSystemRole Controller[index] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    
    public function store(Request $request)
    {
        try{
            $data = UserSystemRole::all();

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("UserSystemRole Controller[store] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function show($id, Request $request)
    {
        try{
            $data = UserSystemRole::find($id);

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("UserSystemRole Controller[show] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function update($id, Request $request)
    {
        try{
            $data = UserSystemRole::find($id);

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("UserSystemRole Controller[update] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function destroy($id, Request $request)
    {
        try{
            $data = UserSystemRole::findOrFail($id);
            $data -> deleted = TRUE;
            $data -> updated_at = now();
            $data -> save();

            return response() -> json(['data' => 'Success'], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("UserSystemRole Controller[destroy] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
}
