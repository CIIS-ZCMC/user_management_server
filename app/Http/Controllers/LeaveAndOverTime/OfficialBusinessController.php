<?php

namespace App\Http\Controllers\LeaveAndOvertime;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\OfficialBusinessResource;
use App\Http\Requests\OfficialBusinessRequest;
use App\Helpers\Helpers;

use App\Models\EmployeeProfile;
use App\Models\Notifications;
use App\Models\OfficialBusiness;

use App\Http\Controllers\Controller;
use App\Models\UserNotifications;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;


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
            $employee_profile = $request->user;
            $employee_area = $employee_profile->assignedArea->findDetails();
            $recommending = ["for recommending approval", "approved", "declined by recommending officer"];
            $approving = ["for approving approval", "approved", "declined by approving officer"];
            $position = $employee_profile->position();
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
            if ($employee_profile->position() === null) {
                $official_business_application = OfficialBusiness::where('employee_profile_id', $employee_profile->id)->get();

                return response()->json([
                    'data' => OfficialBusinessResource::collection($official_business_application),
                    'message' => 'Retrieved all offical business application'
                ], Response::HTTP_OK);
            }

            if ($employee_profile->id === Helpers::getHrmoOfficer()) {
                return response()->json([
                    'data' => OfficialBusinessResource::collection(OfficialBusiness::where('status', 'approved')->get()),
                    'message' => 'Retrieved all offical business application'
                ], Response::HTTP_OK);
            }

            if ($employeeId == 1) {
                $official_business_application = OfficialBusiness::select('official_business_applications.*')
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
            }

            $official_business_application = OfficialBusiness::select('official_business_applications.*')
                ->where(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('official_business_applications.status', $recommending)
                        ->where('official_business_applications.recommending_officer', $employeeId);
                })
                ->orWhere(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('official_business_applications.status', $approving)
                        ->where('official_business_applications.approving_officer', $employeeId);
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
                'data' => OfficialBusinessResource::collection($official_business_application),
                'message' => 'Retrieved all official business application'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function exportCsv()
    {
        $ob_applications = OfficialBusiness::with('employee')
            ->where('status', 'approved')
            ->get();
        // ->where('status', 'approved')


        $response = [];

        foreach ($ob_applications as $ob_application) {
            $employeeName = $ob_application->employee->name();
            $employeeid = $ob_application->employee->employee_id;
            $dateFrom = $ob_application->date_from;
            $dateTo = $ob_application->date_to;
            $purpose = $ob_application->purpose;
            $applied_date = $ob_application->created_at;
            $response[] = [
                'Employee Id' => $employeeid,
                'Employee Name' => $employeeName,
                'Date From' => $dateFrom,
                'Date To' => $dateTo,
                'Purpose' => $purpose,
                'Date Filed' => $applied_date,


            ];
        }
        return ['data' => $response];
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

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OfficialBusinessRequest $request)
    {
        try {
            $user = $request->user;
            $assigned_area = $user->assignedArea->findDetails();

            $employee_profile = $request->user;
            $employeeId = $employee_profile->id;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($employee_profile['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }
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
            $officers = Helpers::getRecommendingAndApprovingOfficer($assigned_area, $user->id);

            if ($officers === null || $officers['recommending_officer'] === null || $officers['approving_officer'] === null) {
                return response()->json(['message' => 'No recommending officer and/or supervising officer assigned.'], Response::HTTP_FORBIDDEN);
            }

            $recommending_officer = $officers['recommending_officer'];
            $approving_officer = $officers['approving_officer'];

            $start = Carbon::parse($request->date_from);
            $end = Carbon::parse($request->date_to);
            $employeeId = $user->id;

            $overlapExists = Helpers::hasOverlappingRecords($start, $end, $employeeId);
            if ($overlapExists) {
                return response()->json(['message' => 'You already have an application for the same dates.'], Response::HTTP_FORBIDDEN);
            } else {

                $data = new OfficialBusiness;

                $data->employee_profile_id = $user->id;
                $data->date_from = $cleanData['date_from'];
                $data->date_to = $cleanData['date_to'];
                $data->purpose = $cleanData['purpose'];
                $data->personal_order_file = $cleanData['personal_order_file']->getClientOriginalName();
                $data->personal_order_size = $cleanData['personal_order_file']->getSize();
                $data->personal_order_path = Helpers::checkSaveFile($cleanData['personal_order_file'], 'official_business');
                $data->certificate_of_appearance = $cleanData['certificate_of_appearance']->getClientOriginalName();
                $data->certificate_of_appearance_size = $cleanData['certificate_of_appearance']->getSize();
                $data->certificate_of_appearance_path = Helpers::checkSaveFile($cleanData['certificate_of_appearance'], 'official_business');
                $data->approving_officer = $approving_officer;
                $data->recommending_officer = $recommending_officer;
                $data->save();


                $employeeProfile = EmployeeProfile::find($employeeId);
                $title = "New Official Business request";
                $description = $employeeProfile->personalInformation->name() . " filed a new official business request";


                $notification = Notifications::create([
                    "title" => $title,
                    "description" => $description,
                    "module_path" => '/ob-requests',
                ]);

                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $recommending_officer,
                ]);

                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($recommending_officer),
                    "data" => new NotificationResource($user_notification)
                ]);

                Helpers::registerSystemLogs($request, $data->id, true, 'Success in storing ' . $this->PLURAL_MODULE_NAME . '.'); //System Logs

                return response()->json([
                    'data' => OfficialBusinessResource::collection(OfficialBusiness::where('id', $data->id)->get()),
                    'logs' => Helpers::registerOfficialBusinessLogs($data->id, $user['id'], 'Applied'),
                    'message' => 'Request Complete.'
                ], Response::HTTP_OK);
            }
        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
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
            $data = OfficialBusiness::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $status = null;
            $log_action = null;
            $employee_profile = $request->user;

            $cleanData['pin'] = strip_tags($request->pin);


            if ($employee_profile['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }


            $employeeProfile = EmployeeProfile::find($data->employee_profile_id);
            $officer = '';

            if ($request->status === 'approved') {
                switch ($data->status) {
                    case 'for recommending approval':
                        if ($employee_profile->id === $data->recommending_officer) {
                            $status = 'for approving approval';
                            $log_action = 'Approved by Recommending Officer';
                        } else {
                            return response()->json([
                                'message' => 'You have no access to approve this request.',
                            ], Response::HTTP_FORBIDDEN);
                        }
                        break;

                    case 'for approving approval':
                        if ($employee_profile->id === $data->approving_officer) {
                            $status = 'approved';
                            $log_action = 'Approved by Approving Officer';
                        } else {
                            return response()->json([
                                'message' => 'You have no access to approve this request.',
                            ], Response::HTTP_FORBIDDEN);
                        }
                        break;
                }
            } else if ($request->status === 'declined') {
                $ob_application_recommending = $data->recommending_officer;
                $ob_application_approving = $data->approving_officer;


                if ($employee_profile->id === $ob_application_recommending) {
                    if ($data->status === 'declined by recommending officer') {
                        return response()->json([
                            'message' => 'You already declined this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    $status = 'declined by recommending officer';
                    $officer = "Recommending";
                } else if ($employee_profile->id === $ob_application_approving) {
                    if ($data->status === 'declined by approving officer') {
                        return response()->json([
                            'message' => 'You already declined this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    $status = 'declined by approving officer';
                    $officer = "Approving";
                }
                $log_action = 'Request Declined';
            }
            $data->update(['status' => $status, 'remarks' => $request->remarks === 'null' || !$request->remarks ? null : $request->remarks]);

            if ($data->status === 'approved') {
                //EMPLOYEE
                $notification = Notifications::create([
                    "title" => "Official Business request approved",
                    "description" => "Your official business request has been approved by your Approving Officer. ",
                    "module_path" => '/ob',
                ]);

                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $data->employee_profile_id,
                ]);

                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($data->employee_profile_id),
                    "data" => new NotificationResource($user_notification)
                ]);
            } else if ($data->status === "declined by recommending officer" || $data->status === "declined by approving officer") {
                //EMPLOYEE
                $notification = Notifications::create([
                    "title" => "Official Business request declined",
                    "description" => "Your official business request has been declined by your" . $officer . "Officer. ",
                    "module_path" => '/ob',
                ]);

                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $data->employee_profile_id,
                ]);

                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($data->employee_profile_id),
                    "data" => new NotificationResource($user_notification)
                ]);
            } else {
                //NOTIFS
                //NEXT APPROVING
                $notification = Notifications::create([
                    "title" =>  "New Official Business request",
                    "description" => $employeeProfile->personalInformation->name() . " filed a new official request.",
                    "module_path" => '/ob-requests',
                ]);

                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $data->approving_officer,
                ]);

                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($data->approving_officer),
                    "data" => new NotificationResource($user_notification)
                ]);

                //EMPLOYEE
                $notification = Notifications::create([
                    "title" => "Official Business request approved",
                    "description" => "Your official business request has been approved by your Recommending Officer. ",
                    "module_path" => '/ob',
                ]);

                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $data->employee_profile_id,
                ]);

                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($data->employee_profile_id),
                    "data" => new NotificationResource($user_notification)
                ]);
            }
            Helpers::registerSystemLogs($request, $id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.'); //System Logs
            return response()->json([
                'data' => OfficialBusinessResource::collection(OfficialBusiness::where('id', $data->id)->get()),
                'logs' => Helpers::registerOfficialBusinessLogs($data->id, $employee_profile['id'], $log_action),
                'message' => $log_action,
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
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
