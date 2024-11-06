<?php

namespace App\Http\Controllers;

use App\Imports\EmployeesRedcapModulesImport;
use App\Models\EmployeeRedcapModules;

use App\Models\RedcapModules;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class RedcapController extends Controller
{

    public function import(Request $request)
    {
        // Validate the file input
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,csv',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Import the Excel file using the import class
        Excel::import(new EmployeesRedcapModulesImport, $request->file('file'));


        return response()->json([
            'message' => "Employee links list successfully registered"
        ], Response::HTTP_OK);
    }

    /**
     * Summary of employessWithRedCapModules
     * 
     * This will retrieve all employee list that has survey authentication link
     * 
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function employessWithRedCapModules(Request $request)
    {
        return response()->json([
            'employees_with_redcap_modules' => EmployeeRedcapModules::all(),
            'message' => "List of employee with redcap surveys link."
        ], Response::HTTP_OK);
    }

    /**
     * Summary of storeRedCapModule
     * 
     * Store new Redcap Module or Redcap Survey Module
     * 
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function storeRedCapModule(Request $request)
    {
        $cleanData = [];
    
        foreach($request as $key => $value){
            if($key === 'user' || $key === 'permissions'){
                continue;
            }

            if($value === null){
                $cleanData[$key] = $value;
                continue;
            }

            if($key === 'origin'){
                $cleanData[$key] = Crypt::encryptString($value);
                continue;
            }

            $cleanData[$key] = strip_tags($value);
        }

        $exist = RedcapModules::where('name', $cleanData['name'])->first();

        if($exist){
            return response()->json(['message' => "Redcap survey module already exist."], Response::HTTP_CONFLICT);
        }

        $new_redcap_module = RedcapModules::create($cleanData);
        
        return response()->json([
            'redcap_module' => $new_redcap_module,
            'message' => "Successfully registered redcap survey module." 
        ], Response::HTTP_OK);
    }
}
