<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\Helpers;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\EventsRequest;
use App\Http\Resources\EventsResource;
use App\Http\Requests\PasswordApprovalRequest;
use App\Models\Events;

class EventsController extends Controller
{
    private $CONTROLLER_NAME = 'Events';
    private $PLURAL_MODULE_NAME = 'Events';
    private $SINGULAR_MODULE_NAME = 'Events';

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $events = Cache::remember('events', $cacheExpiration, function(){
                return Events::all();
            });

            return response()->json([
                'data' => EventsResource::collection($events),
                'message' => 'Events records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function searchEvents(Request $request)
    {
        try{
            $search = strip_tags($request->search);

            $event = Events::where('title',  'LIKE', '%'.$search.'%' )->get();

            if(!$event)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => EventsResource::collection($event),
                'message' => 'Events records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(EventsRequest $request)
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
                            $array_list[$index] = Helpers::checkSaveFile($attachment, 'Events/files');
                            $index++;
                        }
                    }
                    $cleanData['attachments'] = $array_list;
                }
                $cleanData[$key] = strip_tags($value);
            } 

            $event = Events::create($cleanData);

            Helpers::registerSystemLogs($request, $event['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new EventsResource($event),
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
            $event = Events::findOrFail($id);

            if(!$event)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new EventsResource($event),
                'message' => 'Employee Events retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, EventsRequest $request)
    {
        try{
            $event = Events::find($id);

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
                            $array_list[$index] = Helpers::checkSaveFile($attachment, 'Events/files');
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
                'data' => new EventsResource($event),
                'message' => 'Events detail updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $new = Events::findOrFail($id);

            if(!$new)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $new->delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Events deleted created.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
