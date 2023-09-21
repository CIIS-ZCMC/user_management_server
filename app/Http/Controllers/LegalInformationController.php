<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Http\Requests\LegalInformationRequest;
use App\Http\Resources\LegalInformationResource;
use App\Models\LegalInformation;

class LegalInformationController extends Controller
{
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $legal_informations = Cache::remember('legal_informations', $cacheExpiration, function(){
                return LegalInformation::all();
            });

            return response()->json(['data' => LegalInformationResource::collection($legal_informations)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function employeeLegalInformation($id, Request $request)
    {
        try{
            $legal_informations = LegalInformation::where('employee_profile_id',$id)->get();

            return response()->json(['data' => LegalInformationResource::collection($legal_informations)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(Request $request)
    {
        try{
            $cleanData = [];

            $cleanData['uuid'] = Str::uuid();

            foreach ($request->all() as $key => $value) {
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $legal_information = LegalInformation::create($cleanData);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $legal_information = LegalInformation::find($id);

            if(!$legal_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new LegalInformationResource($legal_information)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, Request $request)
    {
        try{
            $legal_information = LegalInformation::find($id);

            if(!$legal_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'answer'){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $legal_information->update($cleanData);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $legal_information = LegalInformation::findOrFail($id);

            if(!$legal_information)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $legal_information->delete();
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function infoLog($module, $message)
    {
        Log::channel('custom-info')->info('Legal Information Controller ['.$module.']: message: '.$errorMessage);
    }

    protected function errorLog($module, $errorMessage)
    {
        Log::channel('custom-error')->error('Legal Information Controller ['.$module.']: message: '.$errorMessage);
    }
}
