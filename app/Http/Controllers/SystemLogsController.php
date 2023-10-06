<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use App\Models\SystemLogs;
use App\Http\Responces\SystemLogsResource;

class SystemLogsController extends Controller
{
    public function index(Request $request)
    {
        try{
            $stations = SystemLogs::all();

            return response()->json(['data' => SystemLogsResource::collection($stations)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $station = SystemLogs::find($id);

            if(!$station)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new SystemLogsResource($station)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $station = SystemLogs::findOrFail($id);

            if(!$station)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $station -> delete();
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    protected function infoLog($module, $message)
    {
        Log::channel('custom-info')->info('Station Controller ['.$module.']: message: '.$errorMessage);
    }

    protected function errorLog($module, $errorMessage)
    {
        Log::channel('custom-error')->error('Station Controller ['.$module.']: message: '.$errorMessage);
    }
}
