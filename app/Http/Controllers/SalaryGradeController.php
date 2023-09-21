<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

use App\Http\Requests\SalaryGradeRequest;
use App\Http\Resources\SalaryGradeResource;
use App\Models\SalaryGrade;

class SalaryGradeController extends Controller
{
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $salary_grades = Cache::remember('salary_grades', $cacheExpiration, function(){
                return SalaryGrade::all();
            });

            return response()->json(['data' => SalaryGradeResource::collection($salary_grades)], Response::HTTP_OK);
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
                $cleanData[$key] = strip_tags($value);
            }

            $salary_grade = SalaryGrade::create($cleanData);

            return response()->json(['data' => $salary_grade], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{  
            $salary_grade = SalaryGrade::find($id);

            if(!$salary_grade)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new SalaryGradeResource($salary_grade)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, Request $request)
    {
        try{ 
            $salary_grade = SalaryGrade::find($id);

            if(!$salary_grade)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $salary_grade -> update($cleanData);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $salary_grade = SalaryGrade::find($id);

            if(!$salary_grade)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $salary_grade -> delete();

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function infoLog($module, $message)
    {
        Log::channel('custom-info')->info('Salary Grade Controller ['.$module.']: message: '.$errorMessage);
    }

    protected function errorLog($module, $errorMessage)
    {
        Log::channel('custom-error')->error('Salary Grade Controller ['.$module.']: message: '.$errorMessage);
    }
}
