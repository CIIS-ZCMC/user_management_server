<?php

namespace App\Http\Controllers\Schedule;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\MonthlyWorkHourRequest;
use App\Http\Resources\MonthlyWorkHoursEmploymentTypeResource;
use App\Http\Resources\MonthlyWorkHoursResource;
use App\Models\EmployeeSchedule;
use App\Models\EmploymentType;
use App\Models\MonthlyWorkHours;
use App\Services\RequestLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class MonthlyWorkHoursController extends Controller
{
    private $CONTROLLER_NAME = 'Monthly Work Hour';
    private $PLURAL_MODULE_NAME = 'monthly work hours';
    private $SINGULAR_MODULE_NAME = 'monthly work hour';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = MonthlyWorkHours::with('employmentType')->get()->groupBy('month_year');
            $formattedData = [];
            $i = 1;

            foreach ($data as $monthYear => $records) {
                $employmentTypes = [];
                foreach ($records as $record) {
                    $employmentTypes[] = [
                        'id' => $record->employmentType->id,
                        'name' => $record->employmentType->name,
                        'work_hours' => $record->work_hours,
                        'monthly_working_hours_id' => $record->id,
                    ];
                }
                $formattedData[] = [
                    'id' => $i++,
                    'month_year' => $monthYear,
                    'employment_type' => $employmentTypes
                ];
            }

            return response()->json(['data' => $formattedData], Response::HTTP_OK);
            // return response()->json(['data' => MonthlyWorkHoursResource::collection($data)], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MonthlyWorkHourRequest $request)
    {
        try {
            $createdEntries = [];
            $data = $request->input('data');
            foreach ($data as $entry) {
                foreach ($entry as $key => $value) {
                    if (empty($value)) {
                        $cleanData[$key] = $value;
                        continue;
                    }

                    if (is_int($value)) {
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($value);
                }

                $data = MonthlyWorkHours::create($cleanData);
                $createdEntries[] = new MonthlyWorkHoursResource($data);
            }

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => $createdEntries,
                'message' => "Successfully saved"
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

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
    public function update(Request $request)
    {
        try {
            $updatedEntries = [];
            $data = $request->input('data');
            foreach ($data as $entry) {
                foreach ($entry as $key => $value) {
                    if (empty($value)) {
                        $cleanData[$key] = $value;
                        continue;
                    }

                    if (is_int($value)) {
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($value);
                }

                $data = MonthlyWorkHours::find($cleanData['monthly_work_hours_id']);

                if (!$data) {
                    return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
                }

                $data->update($cleanData);
                $updatedEntries[] = new MonthlyWorkHoursResource($data);
            }

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => $updatedEntries,
                'message' => "Data Successfully update"
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $data = MonthlyWorkHours::findOrFail($id);
            $data->delete();


            Helpers::registerSystemLogs($request, $id, true, 'Success in delete ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => $data,
                'message' => "Data Successfully deleted"
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getMonthlyWorkHours(Request $request)
    {
        try {
            $monthYearNow = Carbon::parse($request->monthYearNow)->format('m-Y');

            $data = MonthlyWorkHours::where('month_year', $monthYearNow)->first();

            if ($data === null) {
                return response()->json(['data' => []], Response::HTTP_OK);
            }

            return response()->json(['data' => new MonthlyWorkHoursResource($data)], Response::HTTP_OK);

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'getMonthlyWorkHours', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function getMyTotalWorkHours(Request $request)
    {
        try {
            $employee_id = $request->employeeID;
            $month_year = Carbon::parse($request->monthYear)->format('m-Y');

            $total_working_hours = EmployeeSchedule::where('employee_profile_id', $employee_id)
                ->whereHas('schedule', function ($query) use ($month_year) {
                    $query->whereRaw("DATE_FORMAT(date, '%m-%Y') = ?", [$month_year]);
                })
                ->join('schedules', 'employee_profile_schedule.schedule_id', '=', 'schedules.id')
                ->join('time_shifts', 'schedules.time_shift_id', '=', 'time_shifts.id')
                ->sum('time_shifts.total_hours');

            return response()->json(['data' => $total_working_hours], Response::HTTP_OK);

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'getMonthlyWorkHours', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEmploymentType(Request $request)
    {
        try {
            $employment_types = EmploymentType::with('monthlyWorkHours')->get();

            return response()->json([
                'data' => MonthlyWorkHoursEmploymentTypeResource::collection($employment_types),
                'message' => 'Employment type list retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
