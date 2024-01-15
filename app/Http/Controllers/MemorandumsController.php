<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use App\Helpers\Helpers;
use App\Http\Requests\MemorandumsRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\MemorandumsResource;
use App\Models\Memorandums;

class MemorandumsController extends Controller
{
    private $CONTROLLER_NAME = 'Memorandums';
    private $PLURAL_MODULE_NAME = 'Memorandums';
    private $SINGULAR_MODULE_NAME = 'Memorandums';

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $events = Cache::remember('memorandums', $cacheExpiration, function(){
                return Memorandums::all();
            });

            return response()->json([
                'data' => MemorandumsResource::collection($events),
                'message' => 'Memorandums records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function searchMemorandum(Request $request)
    {
        try{
            $search = strip_tags($request->search);

            $event = Memorandums::where('title',  'LIKE', '%'.$search.'%' )->get();

            if(!$event)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => MemorandumsResource::collection($event),
                'message' => 'Memorandums records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(MemorandumsRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'attachments'){
                    $index = 1;
                    $array_list = [];
                    if($value !== null){
                        $attachments = $request->file('attachments');
                        foreach ($attachments as $attachment) {
                            $array_list[$index] = Helpers::checkSaveFile($attachment, 'Memorandums/files');
                            $index++;
                        }
                    }
                    $cleanData['attachments'] = $array_list;
                }
                $cleanData[$key] = strip_tags($value);
            } 

            $event = Memorandums::create($cleanData);

            Helpers::registerSystemLogs($request, $event['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new MemorandumsResource($event),
                'message' => 'Event created successfully'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $event = Memorandums::findOrFail($id);

            if(!$event)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new MemorandumsResource($event),
                'message' => 'Employee Memorandums retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, MemorandumsRequest $request)
    {
        try{
            $event = Memorandums::find($id);

            if(!$event)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'attachments'){
                    $index = 1;
                    $array_list = [];
                    if($value !== null){
                        $attachments = $request->file('attachments');
                        foreach ($attachments as $attachment) {
                            $array_list[$index] = Helpers::checkSaveFile($attachment, 'Memorandums/files');
                            $index++;
                        }
                    }
                    $cleanData['attachments'] = $array_list;
                }
                $cleanData[$key] = strip_tags($value);
            }


            $event->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new MemorandumsResource($event),
                'message' => 'Memorandums detail updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $new = Memorandums::findOrFail($id);

            if(!$new)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $new->delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Memorandums deleted created.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
