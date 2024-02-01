<?php

namespace App\Http\Controllers\Schedule;

use App\Models\EmployeeProfile;

use App\Http\Resources\ScheduleResource;
use App\Http\Requests\ScheduleRequest;
use App\Helpers\Helpers;

use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeScheduleController extends Controller
{ 
    private $CONTROLLER_NAME = 'Employee Schedule';
    private $PLURAL_MODULE_NAME = 'employee schedules';
    private $SINGULAR_MODULE_NAME = 'employee schedule';

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
            
            $date = Carbon::now();
            $user = $request->user;

            $data = EmployeeProfile::join('personal_informations as PI', 'employee_profiles.personal_information_id', '=', 'PI.id')
                                ->select('employee_profiles.id', 'employee_id', 'biometric_id', DB::raw("CONCAT(PI.first_name, ' ', COALESCE(PI.middle_name, ''), '', PI.last_name) AS name"))
                                ->with([
                                    'schedule' => function ($query) use ($date) {
                                        $query->with(['timeShift', 'holiday'])
                                            ->whereYear('date_start', '=', $date->year)
                                            ->whereMonth('date_start', '=', $date->month);
                                    }
                                ])->firstOrFail($user->id);

            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
