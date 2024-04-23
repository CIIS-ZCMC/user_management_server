<?php

namespace App\Http\Controllers\Schedule;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\MonthlyWorkHourRequest;
use App\Http\Resources\MonthlyWorkHoursResource;
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
            return response()->json(['data' => MonthlyWorkHoursResource::collection(MonthlyWorkHours::all())], Response::HTTP_OK);

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
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
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

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new MonthlyWorkHoursResource($data),
                'message' => "Successfully saved"
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

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
    public function update(Request $request, $id)
    {
        try {
            $data = MonthlyWorkHours::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
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

            $data->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new MonthlyWorkHoursResource($data),
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
}
