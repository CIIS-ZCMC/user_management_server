<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use App\Services\RequestLogger;
use App\Http\Requests\LegalInformationQuestionRequest;
use App\Http\Resources\LegalInformationQuestionResource;
use App\Models\LegalInformationQuestion;
use App\Models\SystemLogs;

class LegalInformationQuestionController extends Controller
{
    private $CONTROLLER_NAME = 'Legal Information Question Controller';
    private $PLURAL_MODULE_NAME = 'legal information questions';
    private $SINGULAR_MODULE_NAME = 'legal information question';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $legal_information_questions = Cache::remember('legal_information_questions', $cacheExpiration, function(){
                return LegalInformationQuestion::all();
            });

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => LegalInformationQuestionResource::collection($legal_information_questions),
                'message' => 'Legal information list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(LegalInformationQuestionRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'has_sub_question' || $key === 'legal_iq_id'){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }   

            $legal_information_question = LegalInformationQuestion::create($cleanData);

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new LegalInformationQuestionResource($legal_information_question),
                'message' => 'New legal information question registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
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

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new LegalInformationQuestionResource($legal_information_question),
                'message' => 'Legal information record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
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
                if($key === 'has_sub_question' || $key === 'legal_iq_id'){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $legal_information_question -> update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new LegalInformationQuestionResource($legal_information_question),
                'message' => 'Legal information question record updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
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

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Legal information question record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
