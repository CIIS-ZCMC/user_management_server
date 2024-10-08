<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Models\Designation;
use Carbon\Carbon;
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
            $effective_date = $request->effective_at;
            
            $existing_record = SalaryGrade::whereDate('effective_at', $effective_date)->first();

            if($existing_record !== null){
                return response()->json([
                    'message' => "Salary grade with effective date already exist. If you really want to import this please delete first all salary grade that has effective date of ".Carbon::parse($effective_date)->format("F j, Y")." ."
                ], Response::HTTP_BAD_REQUEST);
            }
    
            $file = $request->file('csv_file');

            $csvData = $this->readCsv($file);

            $this->insertData($csvData, $effective_date);

            $salary_grades = SalaryGrade::all();

            return response()->json([
                'data' => SalaryGradeResource::collection($salary_grades),
                'message' => 'Salary grade imported.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            if ($th->getCode() == 400) {
                return response()->json(['message' => $th->getMessage()], Response::HTTP_BAD_REQUEST);
            }
            
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

    private function insertData($data, $effective_date)
    {
        $new_salary_grade = [];

        try {
            foreach($data as $row){
                if (count($row) !== 10) {
                    continue;
                }
                
                $new_salary_grade[] = [
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
                    'effective_at' => $effective_date,
                ];
            }
        } catch (\Exception $e) {
            throw new \Exception("Import of new salary grade rejected. Please check the file you uploaded.", 400);
        }

       
        foreach($new_salary_grade as $salary){
            SalaryGrade::create([
                'salary_grade_number' => $salary['salary_grade_number'],
                'one' => $salary['one'],
                'two' => $salary['two'],
                'three' => $salary['three'],
                'four' => $salary['four'],
                'five' => $salary['five'],
                'six' => $salary['six'],
                'seven' => $salary['seven'],
                'eight' => $salary['eight'],
                'tranch' => $salary['tranch'],
                'effective_at' => $salary['effective_at'],
            ]);
        }
    }

    public function updateSalaryGradeForJobPosition(Request $request)
    {
        try{
            $effective_date = $request->effective_date;

            $current_salary_grade = SalaryGrade::where('is_active', True)->get();

            foreach($current_salary_grade as $salary_grade){
                $new_salary_grade_data = SalaryGrade::whereDate('effective_at', $effective_date)
                    ->where('salary_grade_number', $salary_grade->salary_grade_number)->first();

                Designation::where('salary_grade_id', $salary_grade->id)->get()->update(['salary_grade_id' => $new_salary_grade_data->id]);
                $salary_grade->update(['is_active' => False]);
                $new_salary_grade_data->update(['is_active' => True]);
            }

            $active_salary_grade = SalaryGrade::where('is_active', True)->get();
            
            return response()->json([
                'data' => SalaryGradeResource::collection($active_salary_grade),
                'message' => 'Job position salary grade updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'updateSalaryGradeForJobPosition', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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

            $check_if_exist =  SalaryGrade::where('salary_grade_number', $cleanData['salary_grade_number'])->where('effective_at', $cleanData['effective_at'])->first();

            if($check_if_exist !== null){
                return response()->json(['message' => 'Salary grade already exist.'], Response::HTTP_FORBIDDEN);
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
    
    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
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
    
    public function destroyOnEffectiveDate($id, Request $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);
            $effective_date = $request->effective_date;

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $salary_grades = SalaryGrade::where('effective_at', $effective_date)->get();

            if(count($salary_grades) === 0)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            foreach($salary_grades as $salary_grade){
                if($salary_grade->is_active){
                    return response()->json(['message' => "You are attempting to delete salary grade that is currently used."], Response::HTTP_FORBIDDEN);
                }
            }

            SalaryGrade::where('effective_at', $effective_date)->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Salary grade record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroyOnEffectiveDate', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
