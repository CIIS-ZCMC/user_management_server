<?php

namespace App\Http\Controllers\Schedule;

use App\Models\TimeShift;
use App\Models\Section;
use App\Http\Resources\TimeShiftResource;
use App\Http\Resources\SectionResource;
use App\Http\Requests\TimeShiftRequest;
use App\Services\RequestLogger;
use App\Helpers\Helpers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

                $cleanData[$key] = strip_tags($value);
            }

            $user = $request->user;
            if ($user != null && $user->position()) {
                $position = $user->position();

                if (
                    $position->position === "Chief" || $position->position === "Department OIC" || $position->position === "Supervisor"
                    || $position->position === "Section OIC" || $position->position === "Unit Head" || $position->position === "Unit OIC"
                ) {

                    $shift = TimeShift::where('first_in', $request->first_in)
                        ->where('first_out', $request->first_out)
                        ->where('second_in', $request->second_in)
                        ->where('second_out', $request->second_out)
                        ->first();

                    if ($shift) {
                        $data = $shift;

                    } else {
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

                        $cleanData['color'] = Helpers::randomHexColor();

                        $data = TimeShift::create($cleanData);
                    }

                    $section = Section::select('id')->where('name', $cleanData['section_name'])->first();

                    if ($section != null) {
                        $query = DB::table('section_time_shift')->where([
                            ['section_id', '=', $section->id],
                            ['time_shift_id', '=', $data->id],
                        ])->first();

                        if ($query) {
                            $msg = 'time shift already exist';
                        } else {
                            $data->section()->attach($section);
                            $msg = 'New time shift registered.';
                        }
                    }

                    Helpers::registerSystemLogs($request, $data['id'], true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
                    return response()->json(['data' => $data, 'message' => $msg], Response::HTTP_OK);

                } else {
                    return response()->json(['message' => 'User not allowed to create'], Response::HTTP_OK);
                }

            } else {
                return response()->json(['message' => 'User no position'], Response::HTTP_OK);
            }

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        try {
            $data = new TimeShiftResource(TimeShift::with(['section'])->findOrFail($id));

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TimeShiftRequest $request, $id)
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
            if ($user != null && $user->position()) {
                $position = $user->position();

                if (
                    $position->position === "Chief" || $position->position === "Department OIC" || $position->position === "Supervisor"
                    || $position->position === "Section OIC" || $position->position === "Unit Head" || $position->position === "Unit OIC"
                ) {

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
                    return response()->json(['data' => $data], Response::HTTP_OK);

                } else {
                    return response()->json(['message' => 'User not allowed to create'], Response::HTTP_OK);
                }

            } else {
                return response()->json(['message' => 'User no position'], Response::HTTP_OK);
            }

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
            $user = $request->user;
            if ($user != null && $user->position()) {
                $position = $user->position();

                if (
                    $position->position === "Chief" || $position->position === "Department OIC" || $position->position === "Supervisor"
                    || $position->position === "Section OIC" || $position->position === "Unit Head" || $position->position === "Unit OIC"
                ) {

                    $data = TimeShift::withTrashed()->findOrFail($id);
                    $data->section()->detach($data->id);

                    if ($data->deleted_at != null) {
                        $data->forceDelete();
                    } else {
                        $data->delete();
                    }

                    Helpers::registerSystemLogs($request, $id, true, 'Success in delete ' . $this->SINGULAR_MODULE_NAME . '.');
                    return response()->json(['data' => $data], Response::HTTP_OK);

                } else {
                    return response()->json(['message' => 'User not allowed to create'], Response::HTTP_OK);
                }

            } else {
                return response()->json(['message' => 'User no position'], Response::HTTP_OK);
            }

        } catch (\Throwable $th) {

            $this->requestLogger->errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
