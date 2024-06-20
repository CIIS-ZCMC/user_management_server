<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthPinApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\Helpers;
use App\Http\Requests\FreedomWallMessagesRequest;
use App\Http\Resources\FreedomWallMessagesResource;
use App\Models\FreedomWallMessages;
use App\Models\Notifications;
use App\Models\UserNotifications;
use App\Http\Resources\NotificationResource;
use App\Models\EmployeeProfile;

class FreedomWallMessagesController extends Controller
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
            // Retrieve all Freedom Wall messages with related employee profile and personal information
            $freedom_wall_messages = FreedomWallMessages::with('employeeProfile.personalInformation')->orderBy('created_at', 'desc')->get();

            // Map the messages to the desired format
            $data = $freedom_wall_messages->map(function ($message) {
                $employeeProfile = $message->employeeProfile;
                $personalInformation = $employeeProfile->personalInformation;

                // Generate the profile URL
                $profile_url = $employeeProfile->profile_url
                    ? config('app.server_domain') . "/photo/profiles/" . $employeeProfile->profile_url
                    : null;

                return [
                    'profile_url' => $profile_url,
                    'name' => $personalInformation->employeeName(),
                    'content_id' => $message->id,
                    'content' => $message->content,
                    'date' => $message->created_at->toDateTimeString(),
                ];
            });

            return response()->json([
                'data' => $data,
                'message' => 'Freedom wall messages retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log error and return internal server error response
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
                'content' => strip_tags($request->input('content')),
            ];

            // Create the message
            $message = FreedomWallMessages::create($cleanData);

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
     * @param FreedomWallMessagesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, FreedomWallMessagesRequest $request)
    {
        try {
            // Find the message by ID
            $freedom_wall_message = FreedomWallMessages::find($id);

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
                'data' => new FreedomWallMessagesResource($freedom_wall_message),
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
            $user = $request->user();
            $cleanData['pin'] = strip_tags($request->input('password'));

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            // Find the message by ID and delete it
            $freedom_wall_message = FreedomWallMessages::findOrFail($id);
            $freedom_wall_message->delete();

            return response()->json(['message' => 'Freedom wall message deleted successfully.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log error and return internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}