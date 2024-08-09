<?php

namespace App\Http\Controllers\PayrollHooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeProfile;
class SessionController extends Controller
{
     public function getUserInfo(Request $request){
        $profileID = $request->profileID;
        $emp = EmployeeProfile::find($profileID);
        return response()->json([
            'authorization_pin' => $emp->authorization_pin,
        ]);

     }
}
