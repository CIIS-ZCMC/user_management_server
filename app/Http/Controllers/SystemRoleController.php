<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\SystemRoleRequest;
use App\Http\Resources\SystemRoleResource;
use App\Models\SystemRole;

class SystemRoleController extends Controller
{   
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $systemRoles = Cache::remember('system_roles', $cacheExpiration, function(){
                return SystemRole::all();
            });

            return response() -> json(['data' => SystemRoleResource::collection($systemRoles)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('index', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(SystemRoleRequest $request)
    {
        try{  
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $systemRole = SystemRole::create($cleanData);

            return response() -> json(['data' => "Success"], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('store', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function show($id, Request $request)
    {
        try{
            $systemRole = SystemRole::find($id);

            if(!$systemRole){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response() -> json(['data' => $systemRole], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function update($id, SystemRoleRequest $request)
    {
        try{
            $systemRole = SystemRole::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $systemRole -> update($cleanData);
            
            return response() -> json(['data' => "Success"], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('update', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function destroy($id, Request $request)
    {
        try{
            $systemRole = SystemRole::findOrFail($id);

            if(!$systemRole){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $data -> delete();

            return response() -> json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('destroy', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
