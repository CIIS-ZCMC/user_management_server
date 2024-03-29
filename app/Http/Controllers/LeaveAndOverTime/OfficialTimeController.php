<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Resources\OfficialTimeResource;
use App\Http\Requests\OfficialTimeRequest;
use App\Helpers\Helpers;

use App\Models\OfficialTime;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class OfficialTimeController extends Controller
{
    private $CONTROLLER_NAME = 'Official Time';
    private $PLURAL_MODULE_NAME = 'official times';
    private $SINGULAR_MODULE_NAME = 'official time';
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $employee_profile   = $request->user;
            $employee_area      = $employee_profile->assignedArea->findDetails();
            $recommending = ["for recommending approval", "for approving approval", "approved", "declined by recommending officer"];
            $approving = ["for approving approval", "approved", "declined by approving officer"];
            $employeeId = $employee_profile->id;


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
                $official_time_application = OfficialTime::where('employee_profile_id', $employee_profile->id)->get();

                return response()->json([
                    'data' => OfficialTimeResource::collection($official_time_application),
                    'message' => 'Retrieved all offical time application'
                ], Response::HTTP_OK);
            }


            if ($employee_profile->id===Helpers::getHrmoOfficer()) {
                return response()->json([
                    'data' => OfficialTimeResource::collection(OfficialTime::where('status', 'approved')->get()),
                    'message' => 'Retrieved all offical business application'
                ], Response::HTTP_OK);
            }

            /**
             * Applied
             * Approved by Recommending Officer
             */

             $official_time_application = OfficialTime::select('official_time_applications.*')
                ->where(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('official_time_applications.status', $recommending)
                        ->where('official_time_applications.recommending_officer', $employeeId);
                })
                ->orWhere(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('official_time_applications.status', $approving)
                        ->where('official_time_applications.approving_officer', $employeeId);
                })
                ->groupBy(
                    'id',
                    'date_from',
                    'date_to',
                    'status',
                    'purpose',
                    'personal_order_file',
                    'personal_order_path',
                    'personal_order_size',
                    'certificate_of_appearance',
                    'certificate_of_appearance_path',
                    'certificate_of_appearance_size',
                    'recommending_officer',
                    'approving_officer',
                    'remarks',
                    'employee_profile_id',
                    'created_at',
                    'updated_at',
                )
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => OfficialTimeResource::collection($official_time_application),
                'message' => 'Retrieved all official business application'
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
            $sql = OfficialTime::where('employee_profile_id', $user->id)->get();
            return response()->json(['data' => OfficialTimeResource::collection($sql)], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OfficialTimeRequest $request)
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

            $start = Carbon::parse($request->date_from);
            $end = Carbon::parse($request->date_to);
            $employeeId = $user->id;

            $overlapExists = Helpers::hasOverlappingRecords($start, $end, $employeeId);
            if ($overlapExists) {
                return response()->json(['message' => 'You already have an application for the same dates.'], Response::HTTP_FORBIDDEN);
            } else {
                    $data = new OfficialTime;

                    $data->employee_profile_id              = $user->id;
                    $data->date_from                        = $cleanData['date_from'];
                    $data->date_to                          = $cleanData['date_to'];
                    $data->purpose                          = $cleanData['purpose'];
                    $data->personal_order_file              = $cleanData['personal_order_file']->getClientOriginalName();;
                    $data->personal_order_size              = $cleanData['personal_order_file']->getSize();
                    $data->personal_order_path              = Helpers::checkSaveFile($cleanData['personal_order_file'], 'official_time');
                    $data->certificate_of_appearance        = $cleanData['certificate_of_appearance']->getClientOriginalName();
                    $data->certificate_of_appearance_size   = $cleanData['certificate_of_appearance']->getSize();
                    $data->certificate_of_appearance_path   = Helpers::checkSaveFile($cleanData['certificate_of_appearance'], 'official_time');
                    $data->approving_officer                = $approving_officer;
                    $data->recommending_officer             = $recommending_officer;
                    $data->save();

                    Helpers::registerSystemLogs($request, $data->id, true, 'Success in storing '.$this->PLURAL_MODULE_NAME.'.'); //System Logs

                    return response()->json([
                        'data' => OfficialTimeResource::collection(OfficialTime::where('id', $data->id)->get()),
                        'logs' =>  Helpers::registerOfficialTimeLogs($data->id, $user['id'], 'Applied'),
                        'msg' => 'Request Complete.'], Response::HTTP_OK);
            }
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
    public function update($id, AuthPinApprovalRequest $request)
    {

        try {
            $data = OfficialTime::findOrFail($id);

            if(!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $status     = null;
            $log_action = null;
            $employee_profile = $request->user;

            $cleanData['pin'] = strip_tags($request->password);


            if ($employee_profile['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
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

                    // default:
                    //     $status = 'declined';
                    //     $log_action = 'Request Declined';
                    // break;
                }
            } else if ($request->status === 'declined') {
                $ot_application_recommending=$data->recommending_officer  ;
                $ot_application_approving=$data->approving_officer  ;


                if($employee_profile->id === $ot_application_recommending)
                {
                    $status='declined by recommending officer';
                }
                else if($employee_profile->id === $ot_application_approving)
                {
                    $status='declined by approving officer';
                }
                $log_action = 'Request Declined';
            }


            $data->update(['status' => $status, 'remarks' => $request->remarks==='null' || !$request->remarks ? null : $request->remarks]);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.'); //System Logs
            return response()->json(['data' => OfficialTimeResource::collection(OfficialTime::where('id', $data->id)->get()),
                                    'logs' => Helpers::registerOfficialTimeLogs($data->id, $employee_profile['id'], $log_action),
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
