<?php

namespace App\Http\Controllers\LeaveAndOvertime;

use App\Http\Resources\OfficialBusinessLogResource;
use App\Http\Resources\OfficialBusinessResource;
use App\Http\Requests\OfficialBusinessRequest;
use App\Helpers\Helpers;

use App\Models\Division;
use App\Models\OfficialBusiness;

use App\Http\Controllers\Controller;
use App\Models\OfficialBusinessLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;


class OfficialBusinessController extends Controller
{
    private $CONTROLLER_NAME = 'Official Business';
    private $PLURAL_MODULE_NAME = 'official businesses';
    private $SINGULAR_MODULE_NAME = 'official business';

    /**
     * Display a listing of the resource.   
     */
    public function index(Request $request)
    {
        try {
            $employee_profile   = $request->user;
            $recommending = ["for recommending approval", "for approving approval", "approved", "declined"];
            $approving = ["for approving approval", "approved", "declined"];

            /**
             * Division Head [approving, recommending] - applications of assigned area
             *  - recommending => [for recommending approval, for approving approval, approved, declined]
             *  - approving => [ for approving approval, approved, declined]
             * 
             * Department Head [recommending] - applications of assigned area
             *  - recommending => [for recommending approval, for approving approval, approved, declined]
             * 
             * Section Supervisor [recommending] - applications of assigned area
             *  - recommending => [for recommending approval, for approving approval, approved, declined]
             * 
             */
            
             /** FOR NORMAL EMPLOYEE */
            if($employee_profile->position() === null){
                $official_business_application = OfficialBusiness::where('employee_profile_id', $employee_profile->id)->get();
                 
                return response()->json([
                    'data' => OfficialBusinessResource::collection($official_business_application),
                    'message' => 'Retrieved all offical business application'
                ], Response::HTTP_OK);
            }

            $official_business_application = OfficialBusiness::select('official_business_applications.*')
                ->join('official_business_application_logs as obal', 'obal.official_business_id', 'official_business_applications.id')
                ->where('obal.action', 'Applied')
                ->whereIn('official_business_applications.status', $recommending)
                ->where('official_business_applications.recommending_officer', $employee_profile->id)
                ->orderBy('official_business_applications.created_at', 'desc')->get();
                
            $official_business_application_approving = OfficialBusiness::select('official_business_applications.*')
                ->join('official_business_application_logs as obal', 'obal.official_business_id', 'official_business_applications.id')
                ->whereIn('obal.action', ['official_business_applications.status', 'Approved by Approving Officer'])
                ->whereIn('official_business_applications.status', $approving)
                ->where('official_business_applications.approving_officer', $employee_profile->id)
                ->orderBy('official_business_applications.created_at', 'desc')->get();

            $official_business_application = [...$official_business_application, ...$official_business_application_approving];

            return response()->json([
                'data' => OfficialBusinessResource::collection($official_business_application),
                'message' => 'Retrieved all offical business application'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {

            $user = $request->user;
            $sql = OfficialBusiness::where('employee_profile_id', $user->id)->get();
            return response()->json(['data' => OfficialBusinessResource::collection($sql)], Response::HTTP_OK);

        } catch (\Throwable $th) {
            
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OfficialBusinessRequest $request)
    {
        try {
            $user           = $request->user;
            $assigned_area  = $user->assignedArea->findDetails();

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (empty($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if ($request->hasFile($key)) {
                    $file = $request->file($key);   
                    $cleanData[$key] = $file;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }
            $officers   = Helpers::getRecommendingAndApprovingOfficer($assigned_area, $user->id);

            $recommending_officer   = $officers['recommending_officer'];
            $approving_officer      = $officers['approving_officer'];

            $data = new OfficialBusiness;

            $data->employee_profile_id              = $user->id;
            $data->date_from                        = $cleanData['date_from'];
            $data->date_to                          = $cleanData['date_to'];
            $data->time_from                        = $cleanData['time_from'];
            $data->time_to                          = $cleanData['time_to'];
            $data->purpose                          = $cleanData['purpose'];
            $data->personal_order_file              = $cleanData['personal_order_file']->getClientOriginalName();;
            $data->personal_order_size              = $cleanData['personal_order_file']->getSize();
            $data->personal_order_path              = Helpers::checkSaveFile($cleanData['personal_order_file'], 'official_business');
            $data->certificate_of_appearance        = $cleanData['certificate_of_appearance']->getClientOriginalName();
            $data->certificate_of_appearance_size   = $cleanData['certificate_of_appearance']->getSize();
            $data->certificate_of_appearance_path   = Helpers::checkSaveFile($cleanData['certificate_of_appearance'], 'official_business');
            $data->approving_officer                = $approving_officer;
            $data->recommending_officer             = $recommending_officer;
            $data->save();

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in storing '.$this->PLURAL_MODULE_NAME.'.'); //System Logs

            return response()->json([
                'data' => OfficialBusinessResource::collection(OfficialBusiness::where('id', $data->id)->get()),
                'logs' =>  Helpers::registerOfficialBusinessLogs($data->id, $user['id'], 'Applied'), 
                'msg' => 'Request Complete.'], Response::HTTP_OK);
        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id, Request $request)
    {
        try {        
            $data = OfficialBusiness::findOrFail($id);

            if(!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $status     = null;
            $log_action = null;

            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            if ($request->status === 'approved') {
                switch ($data->status) {
                    case 'for recommending approval':
                        $status = 'for approving approval';
                        $log_action = 'Approved by Recommending Officer';
                    break;

                    case 'for approving approval':
                        $status = 'approved';
                        $log_action = 'Approved by Approving Officer';
                    break;
                    
                    default:
                        $status = 'declined';
                        $log_action = 'Request Declined';
                    break;
                }
            } else if ($request->status === 'declined') {
                $status = 'declined';
                $log_action = 'Request Declined';
            }
            

            $data->update(['status' => $status, 'remarks' => $request->remarks]);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.'); //System Logs
            return response()->json(['data' => OfficialBusinessResource::collection(OfficialBusiness::where('id', $data->id)->get()),
                                    'logs' => Helpers::registerOfficialBusinessLogs($data->id, $employee_profile['id'], $log_action),
                                    'msg' => $log_action, ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
