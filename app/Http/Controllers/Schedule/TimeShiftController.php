<?php

namespace App\Http\Controllers\Schedule;

use App\Models\TimeShift;
use App\Http\Resources\TimeShiftResource;
use App\Http\Requests\TimeShiftRequest;
use App\Services\RequestLogger;
use App\Helpers\Helpers;

use Illuminate\Http\Response;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TimeShiftController extends Controller
{
    private $CONTROLLER_NAME = 'Time Shift';
    private $PLURAL_MODULE_NAME = 'time shifts';
    private $SINGULAR_MODULE_NAME = 'time shift';

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
            return response()->json(['data' => TimeShiftResource::collection(TimeShift::all())], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TimeShiftRequest $request)
    {
        try {
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (empty($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }
                
                if (is_array($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }
            
            $shift = TimeShift::where('first_in', $request->first_in ?? null)
                ->where('first_out', $request->first_out ?? null)
                ->where('second_in', $request->second_in ?? null)
                ->where('second_out', $request->second_out ?? null)
                ->first();

            if ($shift) {
                return response()->json(['message' => "Time Shift Already Exist"], Response::HTTP_FOUND);
            } 

            if ($cleanData['first_in'] != null && $cleanData['first_out'] != null && $cleanData['second_in'] == null && $cleanData['second_out'] == null) {
                $first_in = Carbon::parse($cleanData['first_in']);
                $first_out = Carbon::parse($cleanData['first_out']);

                $cleanData['total_hours'] = $first_in->diffInHours($first_out);

            } else if ($cleanData['first_in'] != null && $cleanData['first_out'] != null && $cleanData['second_in'] != null && $cleanData['second_out'] != null) {
                $first_in = Carbon::parse($cleanData['first_in']);
                $first_out = Carbon::parse($cleanData['first_out']);

                $second_in = Carbon::parse($cleanData['second_in']);
                $second_out = Carbon::parse($cleanData['second_out']);

                $AM = $first_in->diffInHours($first_out);
                $PM = $second_in->diffInHours($second_out);

                $cleanData['total_hours'] = $AM + $PM;
            }

            $data = TimeShift::create($cleanData);
            
            Helpers::registerSystemLogs($request, $data['id'], true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new TimeShiftResource($data),
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
            $data = TimeShift::findOrFail($id);

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

            $user = $request->user;
            if ($cleanData['first_in'] != null && $cleanData['first_out'] != null && $cleanData['second_in'] == null && $cleanData['second_out'] == null) {
                $first_in = Carbon::parse($cleanData['first_in']);
                $first_out = Carbon::parse($cleanData['first_out']);

                $cleanData['total_hours'] = $first_in->diffInHours($first_out);

            } else if ($cleanData['first_in'] != null && $cleanData['first_out'] != null && $cleanData['second_in'] != null && $cleanData['second_out'] != null) {
                $first_in = Carbon::parse($cleanData['first_in']);
                $first_out = Carbon::parse($cleanData['first_out']);

                $second_in = Carbon::parse($cleanData['second_in']);
                $second_out = Carbon::parse($cleanData['second_out']);

                $AM = $first_in->diffInHours($first_out);
                $PM = $second_in->diffInHours($second_out);

                $cleanData['total_hours'] = $AM + $PM;
            }

            $data->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new TimeShiftResource($data),
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
            $data = TimeShift::withTrashed()->findOrFail($id);

            if ($data->deleted_at != null) {
                $data->forceDelete();
            } else {
                $data->delete();
            }

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
}
