<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;

class TransactionController extends Controller
{   public function index(Request $request)
    {
        try{
            $data = Transaction::all();

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("Transaction Controller[index] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    
    public function store(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|max:255',
                'FK_system_ID' => 'required|string|max:255',
                'FK_user_ID' => 'required|string|max:255',
                'ip_address' => 'required|string|max:255'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $data = [
                'status' => $request->input('first_name'),
                'FK_system_ID' => $request->input('middle_name'),
                'FK_user_ID' => $request->input('last_name'),
                'ip_address' => $request->input('extension_name')
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $data = Transaction::all();
            $data -> status = $clearData['status'];
            $data -> FK_system_ID = $clearData['FK_system_ID'];
            $data -> FK_user_ID = $clearData['FK_user_ID'];
            $data -> ip_address = $clearData['ip_address'];
            $data -> created_at = now();
            $data -> save();

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("Transaction Controller[store] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function show($id, Request $request)
    {
        try{
            $data = Transaction::find($id);

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("Transaction Controller[show] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function update($id, Request $request)
    {
        try{
            $data = Transaction::find($id);

            if(!$data)
            {
                return response() -> json(['message' => "No record found"], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|max:255',
                'FK_system_ID' => 'required|string|max:255',
                'FK_user_ID' => 'required|string|max:255',
                'ip_address' => 'required|string|max:255'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $data = [
                'status' => $request->input('first_name'),
                'FK_system_ID' => $request->input('middle_name'),
                'FK_user_ID' => $request->input('last_name'),
                'ip_address' => $request->input('extension_name')
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $data -> status = $clearData['status'];
            $data -> FK_system_ID = $clearData['FK_system_ID'];
            $data -> FK_user_ID = $clearData['FK_user_ID'];
            $data -> ip_address = $clearData['ip_address'];
            $data -> created_at = now();
            $data -> save();

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("Transaction Controller[update] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    public function destroy($id, Request $request)
    {
        try{
            $data = Transaction::findOrFail($id);
            $data -> deleted = TRUE;
            $data -> updated_at = now();
            $data -> save();

            return response() -> json(['data' => 'Success'], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("Transaction Controller[destroy] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
}
