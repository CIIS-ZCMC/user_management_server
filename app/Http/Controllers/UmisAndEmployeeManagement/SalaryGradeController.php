<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Jobs\UpdateSalaryGradeJob;
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

/**
 * @group Salary Grade
 * 
 * Api for managing salary grade
 * 
 * Action: ex. [Method] functionName - [API] Endpoint
 *  [POST]   importSalaryGrade - [API] `{url}/salary-grade-import`
 *  [PUT]    updateSalaryGradeForJobPosition - [API] `{url}/salary-grade-set-new`
 *  [GET]    index - [API] `{url}/salary-grade-all`
 *  [POST]   store - [API] `{url}/salary-grade`
 *  [GET]    show - [API] `{url}/salary-grade/{id}`
 *  [PUT]    update - [API] `{url}/salary-grade/{id}` 
 *  [DELETE] destroy - [API] `{url}/salary-grade/{id}`
 * 
 * Private Function
 *  readCsv, insertData
 * 
 * Private variable
 *  CONTROLLER_NAME, PLURAL_MOUDLE_NAME, SINGULAR_MODULE_NAME
 */

class SalaryGradeController extends Controller
{
    private $CONTROLLER_NAME = 'Salary Grade';
    private $PLURAL_MODULE_NAME = 'salary grades';
    private $SINGULAR_MODULE_NAME = 'salary grade';

    /**
     * Import salary grade
     * 
     * @param request:
     * {csv_file} file - required a csv file of latest salary grade
     * 
     * @return response:{
     *  200, 
     *  "data" : {
     *      "data": {
     *          "id":1,
     *          "salary_grade_number": 2,
     *          "one": 13819,
     *          "two": 13925,
     *          "three": 14032,
     *          "four": 14140,
     *          "five": 14248,
     *          "six": 14357,
     *          "seven": 14468,
     *          "eight": 14578,
     *          "tranch": 1,
     *          "effective_at": 21-09-2022},
     *       "message": //Message response for success transaction
     *      }
     * } 
     * 
     */
    public function importSalaryGrade(Request $request)
    {
        try{
            $request->validate(['csv_file' => 'required|mimes:csv,txt']);
            $effective_date = $request->effective_at;
            
            $existing_record = SalaryGrade::whereDate('effective_at', $effective_date)->first();

            // Validate existing salary grade base on effective `date`
            if($existing_record !== null){
                return response()->json([
                    'message' => "Salary grade with effective date already exist. If you really want to import this please delete first all salary grade that has effective date of ".Carbon::parse($effective_date)->format("F j, Y")." ."
                ], Response::HTTP_BAD_REQUEST);
            }
    
            $file = $request->file('csv_file');

            $csvData = $this->readCsv($file);

            $this->updateDatabaseSalaryGradeRecord($csvData, $effective_date);

            /**
             * Validate if the effective date is from future update
             * to prevent update of salary grade with past date
             */
            if (Carbon::parse($effective_date)->greaterThan(Carbon::now())) {
                // Parse the effective date and set the time to 5 AM
                $dateToTrigger = Carbon::parse($effective_date)->setTime(5, 0, 0);
                $delay = $dateToTrigger->diffInSeconds(Carbon::now());
                
                /**
                 * Register queue job with specific date
                 * @param {effective_date} date - the effective date of salary grade that will be use to uupdate record.
                 * @param {delay} date - the date this job will be triggered ex. effective_date 5AM
                 */
                UpdateSalaryGradeJob::dispatch($effective_date)->delay($delay);
            }

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

    /**
     * @param {file} - uploaded latest file for salary grade.
     * @return {array} - converted csv data to array
     */
    private function readCsv($file)
    {
        // Use Laravel's CsvReader for better CSV parsing
        $csv = Reader::createFromPath($file->getRealPath(), 'r');
        $csv->setHeaderOffset(0); // Assumes the first row is the header

        return iterator_to_array($csv->getRecords());
    }

    /**
     * @param {csvData} array - latest salary grade to register in database
     * @param {effective_date} date - effective date of new salary grade data 
     */
    private function updateDatabaseSalaryGradeRecord($csvData, $effective_date)
    {
        $new_salary_grade = [];

        // Handle invalid csv file or data.
        try {
            foreach($csvData as $row){
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

        //Insert new salary grade in database
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

    /**
     * @param request:
     * {effective_date} date - date of salary grade will be use to update job position salary grade primary key.
     * 
     * @return response:{
     *  200, 
     *  "data" : {
     *      "data": {
     *          "id":1,
     *          "salary_grade_number": 2,
     *          "one": 13819,
     *          "two": 13925,
     *          "three": 14032,
     *          "four": 14140,
     *          "five": 14248,
     *          "six": 14357,
     *          "seven": 14468,
     *          "eight": 14578,
     *          "tranch": 1,
     *          "effective_at": 21-09-2022},
     *       "message": //Message response for success transaction
     *      }
     * } 
     */
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

    /**
     * Retrieve all salary grade without filter
     */
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
    
    /**
     * Create new salary grade
     * 
     * @param request:
     * {salary_grade_number} integer - actual salary grade ex. 1,2,3 and etc..
     * {one} float - salary amount for step 1
     * {two} float - salary amount for step 2
     * {three} float - salary amount for step 3
     * {four} float - salary amount for step 4
     * {five} float - salary amount for step 5
     * {six} float - salary amount for step 6
     * {seven} float - salary amount for step 7
     * {eight} float - salary amount for step 8
     * {effective_at} date - effective date of the new salary grade
     * {password} string - actual password of requester for authorization purpose
     * 
     * @return response:{
     *  200, 
     *  "data" : {
     *      "data": {
     *          "id":1,
     *          "salary_grade_number": 2,
     *          "one": 13819,
     *          "two": 13925,
     *          "three": 14032,
     *          "four": 14140,
     *          "five": 14248,
     *          "six": 14357,
     *          "seven": 14468,
     *          "eight": 14578,
     *          "tranch": 1,
     *          "effective_at": 21-09-2022},
     *       "message": //Message response for success transaction
     *      }
     * } 
     */
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
    
    /**
     * Retrieve salary grade
     * 
     * @param {id} integer - primary key of salary grade to retrieve.
     */
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
    
    /**
     * Update salary grade
     * 
     * @param {id} integer - primary key of salary grade to update.
     * 
     * 
     * @param request:
     * {salary_grade_number} integer - actual salary grade ex. 1,2,3 and etc..
     * {one} float - salary amount for step 1
     * {two} float - salary amount for step 2
     * {three} float - salary amount for step 3
     * {four} float - salary amount for step 4
     * {five} float - salary amount for step 5
     * {six} float - salary amount for step 6
     * {seven} float - salary amount for step 7
     * {eight} float - salary amount for step 8
     * {effective_at} date - effective date of the new salary grade
     * {password} string - actual password of requester for authorization purpose
     * 
     * @return response:{
     *  200, 
     *  "data" : {
     *      "data": {
     *          "id":1,
     *          "salary_grade_number": 2,
     *          "one": 13819,
     *          "two": 13925,
     *          "three": 14032,
     *          "four": 14140,
     *          "five": 14248,
     *          "six": 14357,
     *          "seven": 14468,
     *          "eight": 14578,
     *          "tranch": 1,
     *          "effective_at": 21-09-2022},
     *       "message": //Message response for success transaction
     *      }
     * } 
     */
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
    
    /**
     * Remove Salary Grade
     * 
     * @param request:
     * {id} integer - primary key of salary grade to remove.
     * {pin} string - user authorizaitoh pin for validating authorize action
     * 
     * @return response: {
        *  200, 
        *  "data": {
        *   "message" : //Message for success transaction
        * }
     * }
     */
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
