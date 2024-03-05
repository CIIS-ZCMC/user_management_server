<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\Helpers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\FreedomWallMessagesRequest;
use App\Http\Resources\FreedomWallMessagesResource;
use App\Models\FreedomWallMessages;

class FreedomWallMessagesController extends Controller
{
    private $CONTROLLER_NAME = 'Civil Service Eligibility';
    private $PLURAL_MODULE_NAME = 'civil service eligibilities';
    private $SINGULAR_MODULE_NAME = 'civil service eligibility';

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $civil_service_eligibilities = Cache::remember('freedom-wall-messages', $cacheExpiration, function(){
                return FreedomWallMessages::all();
            });

            return response()->json([
                'data' => FreedomWallMessagesResource::collection($civil_service_eligibilities),
                'message' => 'Freedom wall messages retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(FreedomWallMessagesRequest $request)
    {
        try{
            $employee_profile = $request->user;
            $cleanData = [];

            $cleanData['employee_profile_id'] = $employee_profile->id;

            foreach ($request->all() as $key => $value) {
                if($key === 'user') continue;
                $cleanData[$key] = strip_tags($value);
            }

            $freedom_wall_message = FreedomWallMessages::create($cleanData);

            return response()->json([
                'data' => new FreedomWallMessagesResource($freedom_wall_message), 
                'message' => 'Freedom wall message created successfully.',
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, FreedomWallMessagesRequest $request)
    {
        try{
            $freedom_wall_message = FreedomWallMessages::find($id);

            if(!$freedom_wall_message)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null)
                {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $freedom_wall_message -> update($cleanData);

            return response()->json([
                'data' =>  new FreedomWallMessagesResource($freedom_wall_message),
                'message'=> 'Freedom wall details updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_UNAUTHORIZED);
            }

            $freedom_wall_message = FreedomWallMessages::findOrFail($id);

            if(!$freedom_wall_message)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $freedom_wall_message -> delete();

            return response()->json(['message' => 'Freedom wall message deleted successfully.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
