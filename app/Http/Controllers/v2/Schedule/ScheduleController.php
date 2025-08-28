<?php

namespace App\Http\Controllers\v2\Schedule;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Resources\EmployeeScheduleResource;
use App\Contracts\Schedule\ScheduleRepositoryInterface;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleRepositoryInterface $scheduleRepository
    ){}
       
    public function index(Request $request)
    {
        $user = $request->user;
        $schedules = $this->scheduleRepository->index($user);

        return response()->json(['data' => $schedules]);

        return (new EmployeeScheduleResource($schedules))
            ->additional([
                'message' => 'Success retrieving personal schedule.'
            ]);
    }
}
