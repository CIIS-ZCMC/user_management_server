<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Http\Requests\LegalInformationQuestionRequest;
use App\Http\Resources\LegalInformationQuestionResource;
use App\Models\LegalInformationQuestion;

class LegalInformationQuestionController extends Controller
{
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $legal_information_questions = Cache::remember('legal_information_questions', $cacheExpiration, function(){
                return LegalInformationQuestion::all();
            });

            return response()->json(['data' => LegalInformationQuestionResource::collection($legal_information_questions)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(LegalInformationQuestionRequest $request)
    {
        try{
            $cleanData = [];

            $cleanData['uuid'] = Str::uuid();

            foreach ($request->all() as $key => $value) {
                if($key === 'is_sub_question' || $key === 'legal_iq_id'){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }   

            $legal_information_question = LegalInformationQuestion::create($cleanData);

            return response()->json(['data' => $legal_information_question], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $legal_information_question = LegalInformationQuestion::findOrFail($id);

            if(!$legal_information_question)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new LegalInformationQuestionResource($legal_information_question)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, LegalInformationQuestionRequest $request)
    {
        try{
            $legal_information_question = LegalInformationQuestion::find($id);

            if(!$legal_information_question)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'is_sub_question' || $key === 'legal_iq_id'){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $legal_information_question -> update($cleanData);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $legal_information_question = LegalInformationQuestion::findOrFail($id);

            if(!$legal_information_question)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $legal_information_question -> delete();
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function infoLog($module, $message)
    {
        Log::channel('custom-info')->info('Legal Information Question Controller ['.$module.']: message: '.$errorMessage);
    }

    protected function errorLog($module, $errorMessage)
    {
        Log::channel('custom-error')->error('Legal Information Question Controller ['.$module.']: message: '.$errorMessage);
    }
}
