<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use App\Helpers\Helpers;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\NewsRequest;
use App\Http\Resources\NewsResource;
use App\Models\News;

class NewsController extends Controller
{    
    private $CONTROLLER_NAME = 'News';
    private $PLURAL_MODULE_NAME = 'News';
    private $SINGULAR_MODULE_NAME = 'News';

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $news = Cache::remember('news', $cacheExpiration, function(){
                return News::all();
            });

            return response()->json([
                'data' => NewsResource::collection($news),
                'message' => 'News records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function searchNews(Request $request)
    {
        try{
            $search = strip_tags($request->search);

            $news = News::where('title',  'LIKE', '%'.$search.'%' )->get();

            if(!$news)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => NewsResource::collection($news),
                'message' => 'News records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(NewsRequest $request)
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
                            $array_list[$index] = Helpers::checkSaveFile($attachment, 'news/files');
                            $index++;
                        }
                    }
                    $cleanData['attachments'] = $array_list;
                }
                $cleanData[$key] = strip_tags($value);
            } 

            $news = News::create($cleanData);

            Helpers::registerSystemLogs($request, $news['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new NewsResource($news),
                'message' => 'New employee News added.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $news = News::findOrFail($id);

            if(!$news)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new NewsResource($news),
                'message' => 'Employee News retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, NewsRequest $request)
    {
        try{
            $news = News::find($id);

            if(!$news)
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
                            $array_list[$index] = Helpers::checkSaveFile($attachment, 'news/files');
                            $index++;
                        }
                    }
                    $cleanData['attachments'] = $array_list;
                }
                $cleanData[$key] = strip_tags($value);
            }


            $news->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new NewsResource($news),
                'message' => 'News detail updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $new = News::findOrFail($id);

            if(!$new)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $new->delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'News deleted created.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
