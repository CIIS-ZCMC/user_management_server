<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
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

            return response() -> json(['data' => SystemResource::collection($systems)], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("System Controller[index] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
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

            return response() -> json(['data' => 'Success'], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("System Controller[store] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    public function show($id, Request $request)
    {
        try{
            $system = System::find($id);

            return response() -> json(['data' => new SystemResource($system)], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("System Controller[show] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
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
            $data -> delete();

            return response() -> json(['data' => 'Success'], 200);
        }catch(\Throwable $th){
            Log::channel('custom-error') -> error("System Controller[destroy] :".$th -> getMessage());
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }
}
