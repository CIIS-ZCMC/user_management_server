<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notifications;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     private $CONTROLLER_NAME = 'Notifications';

    public function getNotificationsById($id)
    {
        try{
            $notifications = Notifications::where('employee_profile_id', $id)->get();
            
            return response()->json([
                'data' => NotificationResource::collection($notifications), 
                'message' => 'Notifications retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    public function destroy(string $id)
    {
        //
    }
}
