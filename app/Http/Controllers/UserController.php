<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\PersonalAccessToken;
use App\Models\UserSystemRole;
use App\Models\SystemRole;
use App\Models\System;

class UserController extends Controller
{

    public function authenticate(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|max:255',
                'password' => 'required|string|max:255',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $data = [
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $user = User::where('email', $cleanData['email']) -> first();
            
            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $user['password'])) {
                return response() -> json(['message' => "UnAuthorized"], 401);
            }

            /**
             * Validate if the user has right to access the system
             */
            if($user -> hasAccess($request))
            {
                return response() -> json(['message' => "UnAuthorized"], 401);
            }

            $abilities = $user -> getSystemRole($request -> getHost(), $user); 

            $profile = $user -> profile();
            $token = $user -> createToken($user -> id, $abilities);

            $data = [];
            $data -> profile;
            $data -> token;
            
            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("User Controller[authenticate] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    
    public function validateRequest(Request $request)
    {
        try{
            $user = $request -> user();
            
            $domain = $request -> getHost();
            $ip = '';
            $method = '';

            /**
             * If request didn't come from User Management Client Side
             */
            if($domain !== 'localhost:5173')
            {
                $ip = $request -> input('ip');
                $method = $request -> input('method');
            }else{
                $ip = $request -> ip();
                $method = $request -> method();
            }

            $system = System::where('domain', $request -> getHost()) -> first();

            $transaction = new Transaction;
            $transaction -> status = $method;
            $transaction -> FK_system_ID = $system;
            $transaction -> FK_user_ID = $user -> id;
            $transaction -> ip = $ip;
            $transaction -> created_at = now();
            $transaction -> save();

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("User Controller[validateRequest] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 200);
        }
    }

    public function logout(Request $request)
    {
        $user = $request -> user();
        $domain = $request -> getHost();
        $systemID = System::where('domain', $domain) -> first();

        $userSystemRoles = $user -> userSystemRoles();

        foreach ($userSystemRoles as $userSystemRole) {
            if($userSystemRole -> FK_system_ID === $systemID)
            {
                $token = $userSystemRole -> token();
                $revoke = $token -> revoke();

                if(!$revoke)
                {
                    break;
                }

                $detach = $userSystemRole -> detach();

                if($detach)
                {
                    return response() -> json(['data' => 'Success'], 200);
                }
            }
        }

        return response() -> json9(['message' => 'Failed to signout.'], 500);
    }

    public function index(Request $request)
    {
        try{
            $data = User::all();

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("User Controller[index] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }


    public function store(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|max:255',
                'password' => 'required|string|max:255',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $data = [
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $user = new User();
            $user -> email = $cleanData['email'];
            $user -> passsword = Hash::make($cleanData['password'].env("SALT_VALUE"));
            $user -> created_at = now();
            $user -> updated_at = now();
            $user -> save();

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("User Controller[store] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
    
    public function show($id,Request $request)
    {
        try{
            $data = User::find($id);

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("User Controller[show] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    public function resetPassword($id,Request $request)
    {
        try{

            $user = User::find($id);

            if(!$user)
            {
                return response() -> json(['message' => "No user found." ], 404);
            }

            $validator = Validator::make($request->all(), [
                'password' => 'required|string|max:255',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $data = [
                'password' => $request->input('password'),
            ];
            
            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $user = new User();
            $user -> passsword = Hash::make($cleanData['password'].env("SALT_VALUE"));
            $user -> updated_at = now();
            $user -> save();

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("User Controller[changePassword] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    public function approved($id, Request $request)
    {
        try{    
            $user = User::find($id);
            $user -> status = 2;
            $user -> updated_at = now();
            $user -> save();

            return $response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("User Controller[approved] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    public function declined($id, Request $request)
    {
        try{    
            $user = User::find($id);
            $user -> status = 3;
            $user -> updated_at = now();
            $user -> save();

            return $response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("User Controller[declined] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    public function deactivate($id, Request $request)
    {
        try{

            $user = User::find($id);

            if(!$user)
            {
                return response() -> json(['message' => "No user found." ], 404);
            }

            $validator = Validator::make($request->all(), [
                'password' => 'required|string|max:255',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $data = [
                'password' => $request->input('password'),
            ];

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $user['password'])) {
                return response() -> json(['message' => "UnAuthorized"], 401);
            }

            
            $user -> deactivate = TRUE;
            $user -> updated_at = now();
            $user -> save();

            return $response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("User Controller[deactivate] :".$th -> getMessage());
            return reponse() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    public function sendOTPEmail(Request $request)
    {
        try{

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("User Controller[sendOTPEmail] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    public function validateOTP(Request $request)
    {
        try{

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("User Controller[validateOTP] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }


    public function destroy($id, Request $request)
    {
        try{
            $user = User::findOrFail($id);
            $user -> deleted = TRUE;
            $user -> updated_at = now();
            $user -> save();

            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
}
