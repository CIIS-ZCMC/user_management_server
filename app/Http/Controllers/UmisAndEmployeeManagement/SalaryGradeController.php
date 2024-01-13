<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\PasswordApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use League\Csv\Reader;
use App\Helpers\Helpers;
use App\Http\Requests\SalaryGradeRequest;
use App\Http\Resources\SalaryGradeResource;
use App\Models\SalaryGrade;

class SalaryGradeController extends Controller
{
    private $CONTROLLER_NAME = 'Salary Grade';
    private $PLURAL_MODULE_NAME = 'salary grades';
    private $SINGULAR_MODULE_NAME = 'salary grade';

    public function importSalaryGrade(Request $request)
    {
        try{
            $request->validate(['csv_file' => 'required|mimes:csv,txt']);
    
            $file = $request->file('csv_file');

            $csvData = $this->readCsv($file);

            $this->insertData($csvData);

            $salary_grades = SalaryGrade::all();

            return response()->json([
                'data' => SalaryGradeResource::collection($salary_grades),
                'message' => 'Salary grade list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function readCsv($file)
    {
        // Use Laravel's CsvReader for better CSV parsing
        $csv = Reader::createFromPath($file->getRealPath(), 'r');
        $csv->setHeaderOffset(0); // Assumes the first row is the header

        return iterator_to_array($csv->getRecords());
    }

    private function insertData($data)
    {
        foreach ($data as $row) {
            if (count($row) !== 10) {
                continue;
            }

            try {
                SalaryGrade::create([
                    'salary_grade_number' => $row['salary_grade_number'] ?? null,
                    'one' => $row['one'] ?? null,
                    'two' => $row['two'] ?? null,
                    'three' => $row['three'] ?? null,
                    'four' => $row['four'] ?? null,
                    'five' => $row['five'] ?? null,
                    'six' => $row['six'] ?? null,
                    'seven' => $row['seven'] ?? null,
                    'eight' => $row['eight'] ?? null,
                    'tranch' => $row['tranch'] ?? null,
                    'effective_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Log or handle the error
            }
        }
    }

    public function index(Request $request)
    {
        try{
            $salary_grades = SalaryGrade::all();

            return response()->json([
                'data' => SalaryGradeResource::collection($salary_grades),
                'message' => 'Salary grade list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(SalaryGradeRequest $request)
    {
        try{ 
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $salary_grade = SalaryGrade::create($cleanData);
            
            return response()->json([
                'data' => new SalaryGradeResource($salary_grade),
                'message' => 'New Salary grade registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
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
            
            return response()->json([
                'data' => new SalaryGradeResource($salary_grade),
                'message' => 'Salary grade record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, SalaryGradeRequest $request)
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

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new SalaryGradeResource($salary_grade),
                'message' => 'Salary grade record updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $salary_grade = SalaryGrade::find($id);

            if(!$salary_grade)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            if(count($salary_grade->designation) > 0){
                return response()->json(['message' => "Some data is using this salary grade record deletion is prohibited."], Response::HTTP_BAD_REQUEST);
            }

            $salary_grade -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Salary grade record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
