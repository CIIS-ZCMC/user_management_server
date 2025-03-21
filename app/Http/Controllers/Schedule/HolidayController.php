<?php

namespace App\Http\Controllers\Schedule;

use App\Helpers\Helpers;
use App\Http\Requests\HolidayRequest;
use App\Http\Resources\HolidayCalendarResource;
use App\Http\Resources\HolidayResource;
use App\Models\Holiday;
use App\Http\Controllers\Controller;
use App\Services\RequestLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class HolidayController extends Controller
{
    private $CONTROLLER_NAME = 'Holiday';
    private $PLURAL_MODULE_NAME = 'holidays';
    private $SINGULAR_MODULE_NAME = 'holiday';

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
            return response()->json(['data' => HolidayResource::collection(Holiday::all())], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HolidayRequest $request)
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

            $holidays = Holiday::where('description', $cleanData['description'])
                ->where('month_day', $cleanData['month_day'])
                ->first();
            if ($holidays) {
                return response()->json(['message' => "Holiday Already Exist"], Response::HTTP_FOUND);
            }

            $data = Holiday::create($cleanData);

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new HolidayResource($data),
                'message' => "Successfully saved"
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $data = Holiday::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (empty($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $data->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new HolidayResource($data),
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
            $data = Holiday::findOrFail($id);
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

    /**
     * Display a listing of the resource to calendar
     */
    public function calendar(Request $request)
    {
        try {
            return response()->json(['data' => HolidayCalendarResource::collection(Holiday::all())], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
