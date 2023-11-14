<?php

namespace App\Http\Controllers\Schedule;

use App\Models\Schedule;
use App\Models\EmployeeProfile;
use App\Http\Resources\ScheduleResource;
use App\Http\Requests\ScheduleRequest;
use App\Services\RequestLogger;
use App\Helpers\Helpers;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use DateTime;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    private $CONTROLLER_NAME = 'Schedule';
    private $PLURAL_MODULE_NAME = 'schedules';
    private $SINGULAR_MODULE_NAME = 'schedule';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            
            Helpers::registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');
            return response()->json(['data' => ScheduleResource::collection(Schedule::all())], Response::HTTP_OK);

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ScheduleRequest $request, $id)
    {
        try {
            
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ScheduleRequest $request)
    {
        try {

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (empty($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (is_array($value)) {
                    $employee_data = [];

                    foreach ($request->all() as $key => $value) {
                        $employee_data[$key] = $value;
                    }

                    $cleanData[$key] = $employee_data;
                    continue;
                }

                if (DateTime::createFromFormat('Y-m-d', $value)) {
                    $cleanData[$key] = Carbon::parse($value);
                    continue;
                }

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }


            $date_start     = Carbon::parse($cleanData['date_start']);    // Replace with your start date
            $date_end       = Carbon::parse($cleanData['date_end']);      // Replace with your end date
            $selected_days  = $cleanData['selected_days'];                // Replace with your selected days
            $selected_dates = [];                                         // Replace with your selected dates

            switch ($cleanData['schedule_type']) {
                case 'dates_only':
                    $current_date = $date_start->copy();

                    while ($current_date->lte($date_end)) {
                        $selected_dates[] = $current_date->toDateString();
                        $current_date->addDay();
                    }
                break;

                case 'days_only' :
                    $year   = Carbon::now()->year;              // Replace with your desired year
                    $month  = $cleanData['selected_month'];     // Replace with your desired month

                    $start_date = Carbon::create($year, $month, 1)->startOfMonth(); // Calculate the first day of the month
                    $end_date   = $start_date->copy()->endOfMonth();                // Calculate the last day of the month

                    $current_date = $start_date->copy();

                    while ($current_date->lte($end_date)) {
                        if (in_array($current_date->englishDayOfWeek, $selected_days)) {
                            $selected_dates[] = $current_date->toDateString();
                        }
                        $current_date->addDay();
                    }

                break;

                default:
                    $current_date = $date_start->copy();
                        
                    while ($current_date->lte($date_end)) {
                        if (in_array($current_date->englishDayOfWeek, $selected_days)) {
                            $selected_dates[] = $current_date->toDateString();
                        }
                        $current_date->addDay();
                    }
                break;
            }
            
            foreach ($selected_dates as $key => $date) {
                $schedule = Schedule::where('time_shift_id',$cleanData['time_shift_id'])
                                    ->where('month',        $cleanData['month'])
                                    ->where('date_start',   $date)
                                    ->where('date_end',     $date)
                                    ->first();

                if (!$schedule) {
                    $data = new Schedule;

                    $data->time_shift_id    = $cleanData['time_shift_id'];
                    $data->month            = $cleanData['month'];
                    $data->date_start       = $date;
                    $data->date_end         = $date;
                    $data->is_weekend       = $cleanData['is_weekend'];
                    $data->save();
                } else {

                    $data = $schedule;
                }
                
                $employee = $request['employee'];
                foreach ($employee as $key => $value) {
                    $employee_id  = EmployeeProfile::select('id')->where('id', $value['employee_id'])->first();

                    if ($employee != null) {

                        $query = DB::table('employee_profile_schedule')->where([
                            ['employee_profile_id', '=', $employee_id->id],
                            ['schedule_id', '=', $data->id],
                        ])->first();
    
                        if ($query) {
                            $msg = 'employee schedule already exist';

                        } else {

                            $data->employee()->attach($employee_id);
                            $msg = 'New employee schedule registered.';
                        }
                    }
                }
            }

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data ,'message' => $msg], Response::HTTP_OK);

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        try {
            $data = new ScheduleResource(Schedule::findOrFail($id));

            if(!$data)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            Helpers::registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $schedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $data = Schedule::findOrFail($id);

            if(!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (empty($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (DateTime::createFromFormat('Y-m-d', $value)) {
                    $cleanData[$key] = Carbon::parse($value);
                    continue;
                }

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $data->time_shift_id    = $cleanData['time_shift_id'];
            $data->holiday_id       = $cleanData['holiday_id'];
            
            $data->month            = $cleanData['month'];
            $data->date_start       = $cleanData['date_start'];
            $data->date_end         = $cleanData['date_end'];
            $data->is_weekend       = $cleanData['is_weekend'];
            $data->status           = $cleanData['status'];
            $data->remarks          = $cleanData['remarks'];
            $data->update();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $data = Schedule::withTrashed()->findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $data->employee()->detach($request->employee_id);

            Helpers::registerSystemLogs($request, $id, true, 'Success in delete '.$this->SINGULAR_MODULE_NAME.'.');
            return response()->json(['data' => $data], Response::HTTP_OK);
        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
