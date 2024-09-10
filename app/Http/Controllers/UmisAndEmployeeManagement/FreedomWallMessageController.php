<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\FreedomWallMessageResource;
use App\Http\Resources\NotificationResource;
use App\Models\EmployeeProfile;
use App\Models\FreedomWallMessage;
use App\Models\Notifications;
use App\Models\UserNotifications;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Http\Requests\AuthPinApprovalRequest;
use App\Models\Like;

class FreedomWallMessageController extends Controller
{
    private $CONTROLLER_NAME = 'Freedom Wall Messages';

    /**
     * Retrieve all Freedom Wall messages.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $current_user = $request->user;
            $currentEmployeeProfileId = $current_user->id;

            $freedom_wall_messages = FreedomWallMessage::with('employeeProfile.personalInformation', 'likes')->orderBy('created_at','asc')->get();

            $data = $freedom_wall_messages->map(function ($message) use ($currentEmployeeProfileId) {
                $employeeProfile = $message->employeeProfile;
                $personalInformation = $employeeProfile->personalInformation;

                $profile_url = $employeeProfile->profile_url
                    ? config('app.server_domain') . "/photo/profiles/" . $employeeProfile->profile_url
                    : null;

                $is_liked = $message->likes->contains('employee_profile_id', $currentEmployeeProfileId);

                return [
                    'profile_url' => $profile_url,
                    'name' => $personalInformation->employeeName(),
                    'content_id' => $message->id,
                    'content' => $message->content,
                    'date' => $message->created_at->toDateTimeString(),
                    'likes' => $message->likes->count(),
                    'like_list' => $message->likes->map(function ($like) {
                        return [
                            'id' => $like->id,
                            'employee_profile_id' => $like->employee_profile_id,
                            'name' => $like->employeeProfile->personalInformation->fullName(),
                            'profile_url' => $like->employeeProfile->profile_url
                                ? config('app.server_domain') . "/photo/profiles/" . $like->employeeProfile->profile_url
                                : null,
                        ];
                    }),
                    'is_liked' => $is_liked,
                ];
            });

            return response()->json([
                'data' => $data,
                'message' => 'Freedom wall messages retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Store a new Freedom Wall message.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Fetch current user details
            $current_user = $request->user;
            $currentEmployeeProfile = EmployeeProfile::find($current_user->id);
            $currentEmployeeName = $currentEmployeeProfile->personalInformation->employeeName();

            // Validate input
            $request->validate([
                'content' => 'required|string',
                'mentions' => 'sometimes|array',
                'mentions.*.id' => 'required_with:mentions|integer|exists:employee_profiles,id',
                'mentions.*.name' => 'required_with:mentions|string',
            ]);

            // Clean the input data
            $cleanData = [
                'employee_profile_id' => $currentEmployeeProfile->id,
                'content' => $request->input('content'),
            ];

            // Create the message
            $message = FreedomWallMessage::create($cleanData);

            // Handle mentions if provided
            $mentionedEmployees = collect();
            if ($request->has('mentions')) {
                $mentionedEmployeeIds = collect($request->input('mentions'))->pluck('id');
                $mentionedEmployees = EmployeeProfile::whereIn('id', $mentionedEmployeeIds)
                    ->with(['personalInformation:id,first_name,last_name,middle_name'])
                    ->get(['id', 'employee_id', 'personal_information_id']);
            }

            // Create notifications for mentioned employees
            foreach ($mentionedEmployees as $mentionedEmployee) {
                $notification = Notifications::create([
                    "title" => "You've been mentioned in a Freedom Wall Message",
                    "description" => "{$currentEmployeeName} mentioned you in a message on the Freedom Wall.",
                    "module_path" => '/calendar',
                ]);

                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $mentionedEmployee->id,
                ]);

                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($mentionedEmployee->id),
                    "data" => new NotificationResource($user_notification)
                ]);
            }

            // Generate the profile URL
            $profile_url = $currentEmployeeProfile->profile_url
                ? config('app.server_domain') . "/photo/profiles/" . $currentEmployeeProfile->profile_url
                : null;

            return response()->json([
                'data' => [
                    'profile_url' => $profile_url,
                    'name' => $currentEmployeeName,
                    'content_id' => $message->id,
                    'content' => $message->content,
                    'date' => $message->created_at->toDateTimeString(),
                    'likes' => 0,
                    'like_list' => [],
                    'is_liked' => false,
                ]
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log error and return internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update an existing Freedom Wall message.
     *
     * @param int $id
     * @param FreedomWallMessageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        try {
            // Find the message by ID
            $freedom_wall_message = FreedomWallMessage::find($id);

            if (!$freedom_wall_message) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            // Clean the input data
            $cleanData = [];
            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = $value === null ? $value : strip_tags($value);
            }

            // Update the message
            $freedom_wall_message->update($cleanData);

            return response()->json([
                'data' => new FreedomWallMessageResource($freedom_wall_message),
                'message' => 'Freedom wall details updated.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log error and return internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a Freedom Wall message.
     *
     * @param int $id
     * @param AuthPinApprovalRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try {
            // Validate the authorization pin
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->input('password'));

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            // Find the message by ID and delete it
            $freedom_wall_message = FreedomWallMessage::findOrFail($id);
            $freedom_wall_message->delete();

            return response()->json(['message' => 'Freedom wall message deleted successfully.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log error and return internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Filter a Freedom Wall messages by year.
     *
     */
    public function filterByYear(Request $request)
    {
        try {
            $current_user = $request->user;
            $year = $request->year;

            $currentEmployeeProfileId = $current_user->id;

            $freedom_wall_messages = FreedomWallMessage::with('employeeProfile.personalInformation', 'likes')
                ->whereYear('created_at', $year)
                ->get();


            $data = $freedom_wall_messages->map(function ($message) use ($currentEmployeeProfileId) {
                $employeeProfile = $message->employeeProfile;
                $personalInformation = $employeeProfile->personalInformation;

                $profile_url = $employeeProfile->profile_url
                    ? config('app.server_domain') . "/photo/profiles/" . $employeeProfile->profile_url
                    : null;

                $is_liked = $message->likes->contains('employee_profile_id', $currentEmployeeProfileId);

                return [
                    'profile_url' => $profile_url,
                    'name' => $personalInformation->employeeName(),
                    'content_id' => $message->id,
                    'content' => $message->content,
                    'date' => $message->created_at->toDateTimeString(),
                    'likes' => $message->likes->count(),
                    'is_liked' => $is_liked,
                ];
            });

            return response()->json([
                'data' => $data,
                'message' => 'Freedom wall messages retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterByYear', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Like a Freedom Wall message.
     *
     * @param int $messageId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function like($messageId, Request $request)
    {
        try {
            $current_user = $request->user;
            $currentEmployeeProfile = EmployeeProfile::find($current_user->id);
            
            $like = Like::create([
                'employee_profile_id' => $currentEmployeeProfile->id,
                'freedom_wall_message_id' => intval($messageId),
            ]);
            
            // Assuming $like->employeeProfile is the related model instance
            $employeeProfile = $like->employeeProfile;
            
            return response()->json([
                'data' => [
                    'id' => $like->id,
                    'employee_profile_id' => $like->employee_profile_id,
                    'freedom_wall_message_id' => $like->freedom_wall_message_id,
                    'liked_by' => [
                        'id' => $like->id,
                        'employee_profile_id' => $like->employee_profile_id,
                        'name' => $employeeProfile->personalInformation->fullName(),
                        'profile_url' => $employeeProfile->profile_url
                        ? config('app.server_domain') . "/photo/profiles/" . $employeeProfile->profile_url
                        : null,
                    ]
                    
                ],
                'message' => 'Message liked successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'like', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Unlike a Freedom Wall message.
     *
     * @param int $messageId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlike($messageId, Request $request)
    {
        try {
            $current_user = $request->user;
            $currentEmployeeProfile = EmployeeProfile::find($current_user->id);

            $like = Like::where('employee_profile_id', $currentEmployeeProfile->id)
                ->where('freedom_wall_message_id', intval($messageId))
                ->first();

            if ($like) {
                $like->delete();
                
                return response()->json([
                    'data' => $like,
                    'message' => 'Message unliked successfully.'
                ], Response::HTTP_OK);
            } else {
                return response()->json(['message' => 'Like not found.'], Response::HTTP_NOT_FOUND);
            }
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'unlike', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}