<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Http\Requests\IdentificationNumberRequest;
use App\Http\Resources\IdentificationNumberResource;
use App\Models\IdentificationNumber;

class IdentificationNumberController extends Controller
{
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $identifations = Cache::remember('identifications', $cacheExpiration, function(){
                return IdentificationNumber::all();
            });

            return response()->json(['data' => IdentificationNumberResource::collection($identifations)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function identificationEmployee($id, Request $request)
    {
        try{
            $identification = IdentificationNumber::where('personal_information_id',$id)->first();

            if(!$identification)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new IdentificationNumberResource($identification)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(IdentificationNumberRequest $request)
    {
        try{
            $cleanData = [];

            $cleanData['uuid'] = Str::uuid();
            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'personal_information_id'){ 
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] =  $this->encryptData(strip_tags($value));
            }

            $identification = IdentificationNumber::create($cleanData);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $identification = IdentificationNumber::find($id);

            if(!$identification)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new IdentificationNumberResource($identification)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, IdentificationNumberRequest $request)
    {
        try{
            $identification = IdentificationNumber::find($id);

            $cleanData = [];
            
            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'personal_information_id'){ 
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] =  $this->encryptData(strip_tags($value));
            }

            $identification -> update($cleanData);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
    public function updateEmployee($id, IdentificationNumberRequest $request)
    {
        try{
            $identification = IdentificationNumber::where('personal_information_id',$id)->first();

            $cleanData = [];
            
            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'personal_information_id'){ 
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] =  $this->encryptData(strip_tags($value));
            }

            $identification -> update($cleanData);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $identification = IdentificationNumber::findOrFail($id);

            if(!$identification)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $identification -> delete();
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyEmployee($id, Request $request)
    {
        try{
            $identification = IdentificationNumber::where('personal_information_id', $id)->first();

            if(!$identification)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $identification -> delete();
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function encryptData($dataToEncrypt)
    {
        return openssl_encrypt($dataToEncrypt, env("ENCRYPT_DECRYPT_ALGORITHM"), env("DATA_KEY_ENCRYPTION"), 0, substr(md5(env("DATA_KEY_ENCRYPTION")), 0, 16));
    }

    protected function infoLog($module, $message)
    {
        Log::channel('custom-info')->info('IdentificationNumber Controller ['.$module.']: message: '.$errorMessage);
    }

    protected function errorLog($module, $errorMessage)
    {
        Log::channel('custom-error')->error('IdentificationNumber Controller ['.$module.']: message: '.$errorMessage);
    }
}
