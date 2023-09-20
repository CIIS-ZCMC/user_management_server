<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

use App\Models\System;
use App\Http\Requests\SystemRequest;
use App\Http\Resources\SystemResource;

class SystemController extends Controller
{   
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $systems = Cache::remember('systems', $cacheExpiration, function(){
                return System::all();
            });

            return response() -> json(['data' => SystemResource::collection($systems)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('index', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(SystemRequest $request)
    {
        try{
            $cleanData = [];

            $cleanData['uuid'] = Str::uuid();

            foreach ($request->all() as $key => $value) {
                if($key === 'domain')
                {
                    $cleanData[$key] = Crypt::encrypt($value);
                    continue;
                }
                $cleanData[$key] = strip_tags($value); 
            }

            $data = System::create($cleanData);

            return response() -> json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('store', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try{
            $system = System::find($id);

            return response() -> json(['data' => new SystemResource($system)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function update($id, SystemRequest $request)
    {
        try{
            $data = System::find($id);
            
            if (!$data) {
                return response()->json(['message' => 'No record found.'], 404);
            }
            
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'domain')
                {
                    $cleanData[$key] = Crypt::encrypt($value);
                    continue;
                }
                $cleanData[$key] = strip_tags($value); 
            }

            $data -> update($cleanData);

            return response() -> json(['data' => "Success"], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('update', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function destroy($id, Request $request)
    {
        try{
            $system = System::findOrFail($id);

            if(!$system){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $system -> delete();

            return response() -> json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('destroy', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function infoLog($module, $message)
    {
        Log::channel('custom-info')->info('Station Controller ['.$module.']: message: '.$errorMessage);
    }

    protected function errorLog($module, $errorMessage)
    {
        Log::channel('custom-error')->error('Station Controller ['.$module.']: message: '.$errorMessage);
    }
}
