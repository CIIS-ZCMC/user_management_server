<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\LegalInformationQuestionRequest;
use App\Http\Resources\LegalInformationQuestionResource;
use App\Models\LegalInformationQuestion;

class LegalInformationQuestionController extends Controller
{
    private $CONTROLLER_NAME = 'Legal Information Question Controller';
    private $PLURAL_MODULE_NAME = 'legal information questions';
    private $SINGULAR_MODULE_NAME = 'legal information question';

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $legal_information_questions = Cache::remember('legal_information_questions', $cacheExpiration, function(){
                return LegalInformationQuestion::where('legal_iq_id', null)->orderBy('order_by', 'asc')->get();
            });

            return response()->json([
                'data' => LegalInformationQuestionResource::collection($legal_information_questions),
                'message' => 'Legal information list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(LegalInformationQuestionRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }   

            $legal_information_question = LegalInformationQuestion::create($cleanData);

            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new LegalInformationQuestionResource($legal_information_question),
                'message' => 'New legal information question registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
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

            return response()->json([
                'data' => new LegalInformationQuestionResource($legal_information_question),
                'message' => 'Legal information record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
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

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new LegalInformationQuestionResource($legal_information_question),
                'message' => 'Legal information question record updated.'
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

            $legal_information_question = LegalInformationQuestion::findOrFail($id);

            if(!$legal_information_question)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $legal_information_question -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Legal information question record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
