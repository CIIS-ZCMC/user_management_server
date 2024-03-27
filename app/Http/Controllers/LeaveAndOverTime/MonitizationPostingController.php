<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Requests\PasswordApprovalRequest;

use App\Models\MonitizationPosting;
use App\Models\EmployeeLeaveCredit;
use App\Models\LeaveType;
use App\Models\MonitizationCandidate;


class MonitizationPostingController extends Controller
{
    public function index(Request $request)
    {
        try{ 
            $mone_applications=[];
            
            $mone_applications = MonitizationPosting::all();
          
           
             return response()->json([
                'data' => $mone_applications,
                'message' => 'Retrieve posting records.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showCandidates($id, Request $request)
    {
        try{ 
            $monitizations = MonitizationPosting::find($id);

            $candidates = $monitizations->candidates;
           
            return response()->json([
                'data' => $candidates,
                'message' => 'Retrieve mosting post candidate records.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkForSLMonitization($id, Request $request)
    {
        try{ 
            $employee_profile = $request->user;

            $employee_leave_credits = EmployeeLeaveCredit::where('leave_type_id', LeaveType::where('code', 'SL')->first()->id)
                    ->where('employee_profile_id', $employee_profile->id)->first();
           
            return response()->json([
                'data' => [
                    'employee_leave_credits' => $employee_leave_credits,
                    'is_allowed_for_sick_leave_monitization' => $employee_leave_credits->total_leave_credits >= 15
                ],
                'message' => 'Retrieve mosting post candidate records.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try{
            $cleanData = [];

            foreach($request->all() as $key => $value)
            {
                if($key === 'user') continue;
                if($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $monitization =  MonitizationPosting::create($cleanData);

            $employee_leave_credits = EmployeeLeaveCredit::select('ep.id')->join('employee_profiles as ep', 'ep.id', 'employee_leave_credits.employee_profile_id')
                ->where('employee_leave_credits.leave_type_id', LeaveType::where('code', 'VL')->first()->id)
                ->where('employee_leave_credits.total_leave_credits', '>=', 15)->get();
      
            $candidates = [];

            foreach($employee_leave_credits as $employee_leave_credit){
                $new = MonitizationCandidate::create([
                    'employee_profile_id' => $employee_leave_credit->id,
                    'montiziation_posting_id' => $monitization->id
                ]);
                $candidates[] = $new;
            }

            return response()->json([
                'data' => [
                    'post' => $monitization,
                    'candidates' => count($candidates)
                ],
                'message' => "Monitization Post has been created."
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, Request $request)
    {
        try{
            $cleanData = [];
            $monitization = MonitizationPosting::find($id);

            if($monitization){
                return response()->json(['message' => 'No monitization found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($request->all() as $key => $value)
            {
                if($key === 'user') continue;
                if($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $monitization->update($cleanData);

            return response()->json([
                'data' => $monitization,
                'message' => "Monitization Post has been updated."
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{ 
            $employee_profile = $request->user;

            $pin = strip_tags($request->password);

            if($employee_profile->authorization_pin === $pin)
            {
                return response()->json(['message' => "Invalid pin."], Response::HTTP_FORBIDDEN);
            }

            $monitization = MonitizationPosting::find($id);

            if($monitization){
                return response()->json(['message' => 'No monitization found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($monitization->candidates as $value){
                $value->delete();
            }

            $monitization->delete();
           
            return response()->json([
                'message' => 'Monitization and candidates has successfully deleted.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

