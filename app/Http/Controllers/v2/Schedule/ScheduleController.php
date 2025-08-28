<?php

namespace App\Http\Controllers\v2\Schedule;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Resources\v2\PersonalScheduleResource;
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

        return PersonalScheduleResource::collection($schedules) 
            ->additional([
                'message' => 'Success retrieving personal schedule.'
            ]);
    }
}
