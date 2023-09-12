<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\CivilServiceEligibilityRequest;
use App\Models\CivilServiceEligibility;

class CivilServiceEligibilityController extends Controller
{
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $civil_service_eligibilities = Cache::remember('civil_service_eligibilities', $cacheExpiration, function(){
                return CivilServiceEligibility::all();
            });

            return response()->json(['data' => $civil_service_eligibilities], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function store(CivilServiceEligibilityRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $civil_service_eligibility = CivilServiceEligibility::create([$cleanData]);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $civil_service_eligibility = CivilServiceEligibility::findOrFail($id);

            if(!$civil_service_eligibility)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => $civil_service_eligibility], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function update($id, CivilServiceEligibilityRequest $request)
    {
        try{
            $civil_service_eligibility = CivilServiceEligibility::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $civil_service_eligibility = CivilServiceEligibility::update([$cleanData]);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $civil_service_eligibility = CivilServiceEligibility::findOrFail($id);

            if(!$civil_service_eligibility)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $civil_service_eligibility -> delete();
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
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
