<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use App\Models\User;

use App\Http\Requests\SignInRequest;
use App\Http\Requests\UserRegistrationRequest;

use App\Http\Resources\AllUserResource;
use App\Http\Resources\SignInResource;

class UserController extends Controller
{

    public function signIn(SignInRequest $request)
    {
        try {

            $data = [
                'employee_id' => $request->employee_id,
                'password' => $request->password,
            ];

            $cleanData = [];

            foreach ($data as $key => $value) {
                if($key === 'public_key'){
                    break;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $user = User::where('employee_id', $cleanData['employee_id'])->first();

            if (!$user) {
                return response()->json(['message' => "No account found with employee_id ".$cleanData['employee_id'].'.'], Response::HTTP_UNAUTHORIZED);
            }            

            $decryptedPassword = Crypt::decryptString($user->password_encrypted);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Employee id or password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            if (!$user->isAprroved()) {
                return response()->json(['message' => "Your account is not approved yet."], Response::HTTP_UNAUTHORIZED);
            }

            $token = $user->createToken();

            $employee_profile = $user->employeeProfile;

            $name = $employee_profile->first_name.' '.$employee_profile->last_name;
            $position = $employee_profile->employmentPosition->name;
            $department = $employee_profile->department->name;    

            $dataToEncrypt = [
                'name' => $name,
                'department' => $department,
                'position' => $position
            ];

            return response()
                ->json(['data' =>  $dataToEncrypt], Response::HTTP_OK)
                ->cookie(env('COOKIE_NAME'), json_encode(['token' => $token]), 60, '/', env('SESSION_DOMAIN'), true);
        } catch (\Throwable $th) {
            $this->log('authenticate', $th->getMessage());
            return response()->json(['message' => openssl_error_string()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function isAuthenticated(Request $request)
    {
        try{
            $user = $request->user;
            
            $employee_profile = $user->employeeProfile;

            $name = $employee_profile->first_name.' '.$employee_profile->last_name;
            $position = $employee_profile->employmentPosition->name;
            $department = $employee_profile->department->name;    

            $dataToEncrypt = json_encode([
                'name' => $name,
                'department' => $department,
                'position' => $position
            ]);

            return response()->json(['data' => $dataToEncrypt], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->log('isAuthenticated', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function signOut(Request $request)
    {
        try{
            $user = $request->user;
    
            $accessToken = $user->accessToken;
            
            foreach ($accessToken as $token) {
                $token->delete();
            }

            return response()->json(['data' => '/'], Response::HTTP_OK)->cookie(env('COOKIE_NAME'), '', -1);;
        }catch(\Throwable $th){
            $this->log('logout', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index(Request $request)
    {
        try{
            $ip = $request -> ip();

            $cacheExpiration = Carbon::now()->addDay();

            $user = Cache::remember('users', $cacheExpiration, function(){
                return User::all();
            });

            return response()->json(['data' => AllUserResource::collection($user)], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->log('index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(UserRegistrationRequest $request)
    {
        try{
            $data = [
                'employee_id' => $request->input('employee_id'),
                'password' => $request->input('password'),
            ];

            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $user = User::create([
                'employee_id' => $cleanData->employee_id,
                'password' => Crypt::encrypt(Hash::make($cleanData['password'].env("SALT_VALUE")))
            ]);

            return response()->json(['message' => "Employee account has been successfully created."], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            $this->log('store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $data = User::find($id);

            return response()->json(['data' => $data], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->log('show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resetPassword($id, Request $request)
    {
        try {

            $user = User::find($id);

            if (!$user) {
                return response()->json(['message' => "No user found."], Response::HTTP_NOT_FOUND);
            }

            $validator = Validator::make($request->all(), [
                'password' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Data requirements did not match.'], Response::HTTP_BAD_REQUEST);
            }

            $data = [
                'password' => $request->input('password'),
            ];

            $cleanData = [];

            foreach ($data as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $user = new User;
            $user->passsword = Crypt::encrypt(Hash::make($cleanData['password'].env("SALT_VALUE")));
            $user->updated_at = now();
            $user->save();

            return response()->json(['data' => "Success"], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->log('resetPassword', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approved($id, Request $request)
    {
        try {
            $user = User::find($id);
            $user->status = 2;
            $user->updated_at = now();
            $user->save();

            return response()->json(['data' => "Success"], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->log('approved', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function declined($id, Request $request)
    {
        try {
            $user = User::find($id);
            $user->status = 3;
            $user->updated_at = now();
            $user->save();

            return response()->json(['data' => "Success"], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->log('declined', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deactivate($id, Request $request)
    {
        try {

            $user = User::find($id);

            if (!$user) {
                return response()->json(['message' => "No user found."], Response::HTTP_NOT_FOUND);
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

            if (!Hash::check($cleanData['password'] . env("SALT_VALUE"), $user['password'])) {
                return response()->json(['message' => "UnAuthorized"], Response::HTTP_UNAUTHORIZED);
            }


            $user->deactivate = TRUE;
            $user->updated_at = now();
            $user->save();

            return $response()->json(['data' => "Success"], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->log('deactivate', $th->getMessage());
            return reponse()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function sendOTPEmail(Request $request)
    {
        try {

            return response()->json(['data' => "Success"], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->log('sendOTPEmail', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function validateOTP(Request $request)
    {
        try {

            return response()->json(['data' => "Success"], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->log('validateOTP', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function destroy($id, Request $request)
    {
        try {
            $user = User::findOrFail($id);
            $user->deleted = TRUE;
            $user->updated_at = now();
            $user->save();

            return response()->json(['data' => "Success"], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->log('destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function log($action, $errorMessage)
    {
        Log::channel('custom-error')->error("User Controller[".$action."] :" . $errorMessage);
    }
}
