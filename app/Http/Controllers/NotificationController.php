<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notifications;
use App\Models\UserNotifications;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private $CONTROLLER_NAME = 'Notifications';

    public function store(Request $request){
        try{

            $notification = Notifications::create([
                "title" => "New Referral",
                "description" => "You have received a new message from John Doe. You have received a new message from John Doe. You have received a new message from John Doe. s",
                "module_path" => "leave",
            ]);

            UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => 1
            ]);

            UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => 1
            ]);

            UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => 1
            ]);

            UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => 1
            ]);

            UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => 1
            ]);

            UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => 1
            ]);
            
            return response()->json(['message' => 'Notifications retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getNotificationsById(Request $request)
    {
        try{
            $user = $request->user;
            $notifications = UserNotifications::where('employee_profile_id', $user->id)->get();
            
            return response()->json([
                'data' => NotificationResource::collection($notifications), 
                'message' => 'Notifications retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function seen($id, Request $request)
    {
        try{
            $notification = UserNotifications::find($id);

            $notification->update(['seen' => 1]);
            
            return response()->json([
                'data' => new NotificationResource($notification), 
                'message' => 'Notifications retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function seenMultipleNotification(Request $request)
    {
        try{
            $notification_ids = $request->notification_ids;

            UserNotifications::whereIn('id', $notification_ids)->update(['seen' => 1]);

            $notification = UserNotifications::whereIn('id', $notification_ids)->get();
            
            return response()->json([
                'data' => NotificationResource::collection($notification), 
                'message' => 'Notifications retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    public function destroy($id, Request $request)
    {
        try{
            $user_notification = UserNotifications::find($id);  

            if(!$user_notification){
                return response()->json(['message' => "No record found"], Response::HTTP_BAD_REQUEST);
            }

            $user_notification->delete();

            return response()->json([ 
                'message' => 'Notification delete.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
