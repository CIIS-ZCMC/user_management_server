<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\EmployeeProfileRequest;
use App\Models\EmployeeProfile;

use App\Http\Requests\ProfileRequest;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $employee_profiles = Cache::remember('employee_profiles', $cacheExpiration, function(){
                return EmployeeProfile::all();
            });

            return response()->json(['data' => $employee_profiles], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function store(EmployeeProfileRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'profile_image')
                {
                    $cleanData[$key] = $this->check_save_file($request);
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            if ($request->file('profile_image')->isValid()) {
                $file = $request->file('profile_image');
                $fileName = 'file_name.png';
    
                $file->move(public_path('employee/profiles'), $fileName);
    
                return response()->json(['message' => 'File uploaded successfully'], 200);
            }

            $employee_profile = EmployeeProfile::create([$cleanData]);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::findOrFail($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => $employee_profile], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function update($id, EmployeeProfileRequest $request)
    {
        try{
            $employee_profile = EmployeeProfile::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) { 
                if($key === 'profile_image')
                {   continue;   }
                $cleanData[$key] = strip_tags($value);
            }

            $employee_profile = EmployeeProfile::update([$cleanData]);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function updateEmployeeProfile($id, EmployeeProfileRequest $request)
    {
        try{
            $employee_profile = EmployeeProfile::find($id);

            $file_value = $this->check_save_file($request->file('profile_image'));

            $employee_profile = EmployeeProfile::update([$file_value]);

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    

    public function destroy($id, Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::findOrFail($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $employee_profile -> delete();
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    protected function check_save_file($request)
    {
        $FILE_URL = 'employee/profiles';
        $fileName = '';

        if ($request->file('profile_image')->isValid()) {
            $file = $request->file('profile_image');
            $filePath = $file->getRealPath();

            $finfo = new \finfo(FILEINFO_MIME);
            $mime = $finfo->file($filePath);
            
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

            if (!in_array($mime, $allowedMimeTypes)) {
                return response()->json(['message' => 'Invalid file type'], 400);
            }

            // Check for potential malicious content
            $fileContent = file_get_contents($filePath);

            if (preg_match('/<\s*script|eval|javascript|vbscript|onload|onerror/i', $fileContent)) {
                return response()->json(['message' => 'File contains potential malicious content'], 400);
            }

            $file = $request->file('profile_image');
            $fileName = Hash::make(time()) . '.' . $file->getClientOriginalExtension();

            $file->move(public_path($FILE_URL), $fileName);
        }
        
        return $fileName;
    }

    protected function infoLog($module, $message)
    {
        Log::channel('custom-info')->info('Employee Profile Controller ['.$module.']: message: '.$errorMessage);
    }

    protected function errorLog($module, $errorMessage)
    {
        Log::channel('custom-error')->error('Employee Profile Controller ['.$module.']: message: '.$errorMessage);
    }
}
