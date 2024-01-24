<?php

namespace App\Http\Controllers\LeaveAndOvertime;

use App\Http\Resources\OfficialBusinessLogResource;
use App\Http\Resources\OfficialBusinessResource;
use App\Http\Requests\OfficialBusinessRequest;
use App\Helpers\Helpers;

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
            $user                   = $request->user;
            $sql                    = OfficialBusiness::all();
            $model                  = null;

            foreach ($sql as $key => $value) {
                switch ($value->status) {
                    case 'for recommending approval':
                        $model = OfficialBusiness::where('recommending_officer', $user->id)->get();
                    break;
                    
                    case 'for approving approval':
                        $model = OfficialBusiness::where('approving_officer', $user->id)->get();
                    break;
                    
                    default:
                        $model = OfficialBusiness::where('employee_profile_id', $user->id)->get();
                    break;
                }
            }

            return response()->json([ 'data' => OfficialBusinessResource::collection($model)], Response::HTTP_OK);

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
            return response()->json(['data' => OfficialBusinessResource::collection(OfficialBusiness::where('id', $data->id)->get()),
                                    'logs' =>  Helpers::registerOfficialBusinessLogs($data->id, $user['id'], 'for recommending approval'), 
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
    public function update($id, Request $request,)
    {
        try {            
            $data = OfficialBusiness::findOrFail($id);

            if(!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $status     = null;

            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            } else {
                if ($request->status === 'approved') {
                    switch ($data->status) {
                        case 'for recommending approval':
                            $status = 'for approving approval';
                        break;
    
                        case 'for approving approval':
                            $status = 'approved';
                        break;
                        
                        default:
                            $status = 'declined';
                        break;
                    }
                } else if ($request->status === 'declined') {
                    $status = 'declined';
                }
            }

            $data->update(['status' => $status, 'remarks' => $request->remarks]);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.'); //System Logs
            return response()->json(['data' => OfficialBusinessResource::collection(OfficialBusiness::where('id', $data->id)->get()),
                                    'logs' => Helpers::registerOfficialBusinessLogs($data->id, $employee_profile['id'], 'store'),
                                    'msg' => $status, ], Response::HTTP_OK);

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
