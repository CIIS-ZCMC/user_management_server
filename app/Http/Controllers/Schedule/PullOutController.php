<?php

namespace App\Http\Controllers\Schedule;

use App\Models\AssignArea;
use App\Models\Department;
use App\Models\Division;
use App\Models\PullOut;
use App\Models\EmployeeProfile;
use App\Models\Section;

use App\Http\Resources\PullOutResource;
use App\Http\Requests\PullOutRequest;
use App\Helpers\Helpers;

use App\Models\Unit;
use Illuminate\Http\Response;

use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PullOutController extends Controller
{
    private $CONTROLLER_NAME = 'Pull Out';
    private $PLURAL_MODULE_NAME = 'pull outs';
    private $SINGULAR_MODULE_NAME = 'pull out';
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            return response()->json(['data' => PullOutResource::collection(PullOut::all())], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {

            $user = $request->user;
            $data = PullOut::where('requesting_officer', $user->id)->get();
            return response()->json(['data' => PullOutResource::collection($data)], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PullOutRequest $request)
    {
        try {
            $user = $request->user;

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

                if (is_array($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (strtotime($value)) {
                    $datetime = Carbon::parse($value);
                    $cleanData[$key] = $datetime->format('Y-m-d'); // Adjust the format as needed
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $data = null;
            $approving_officer = null;

            $selectedEmployeeIds = array_column($cleanData['employee'], 'employee_id');
            $employees = EmployeeProfile::whereIn('id', $selectedEmployeeIds)->get();
            foreach ($employees as $employee) {
                $employeeArea = $employee->assignedArea->findDetails();

                switch ($employeeArea['sector']) {
                    case 'Division':
                        $division = Division::where('id', $employeeArea['details']->id)->first();
                        $approving_officer = $division->chief_employee_profile_id;
                        break;
                    case 'Department':
                        $department = Department::where('id', $employeeArea['details']->id)->first();
                        $approving_officer = $department->head_employee_profile_id;
                        break;
                    case 'Section':
                        $section = Section::where('id', $employeeArea['details']->id)->first();
                        $approving_officer = $section->supervisor_employee_profile_id;
                        break;
                    case 'Unit':
                        $unit = Unit::where('id', $employeeArea['details']->id)->first();
                        $approving_officer = $unit->head_employee_profile_id;
                        break;
                }

                $selectedEmployees[] = $employee;
            }

            foreach ($selectedEmployees as $selectedEmployee) {
                $data = PullOut::create(array_merge($cleanData, [
                    'employee_profile_id' => $selectedEmployee->id,
                    'requesting_officer' => $user->id,
                    'approving_officer' => $approving_officer,
                    'status' => 'pending',
                ]));
            }

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new PullOutResource($data),
                'logs' => Helpers::registerPullOutLogs($data->id, $user->id, 'Store'),
                'msg' => 'Pull out requested'
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function edit(Request $request)
    {
        try {
            $user = $request->user;

            if ($user->employee_id === "1918091351") {
                $model = PullOut::all();
            } else {
                $model = PullOut::where('approving_officer', $user->id)
                    ->where('deleted_at', null)
                    ->get();
            }
            return response()->json([
                'data' => PullOutResource::collection($model),
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update($id, Request $request)
    {
        try {
            $user = $request->user;

            $data = PullOut::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $status = null;
            switch ($request->approval_status) {
                case 'approved':
                    $status = 'approved';
                    break;

                case 'declined':
                    $status = 'declined';

                default;
            }

            $data->update(['status' => $status, 'remarks' => $request->remarks, 'approval_date' => Carbon::now()]);

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in updating.' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => new PullOutResource($data),
                'logs' => Helpers::registerPullOutLogs($data->id, $user->id, $status),
                'message' => 'Pull out is ' . $status
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $data = PullOut::withTrashed()->findOrFail($id);

            if ($data->deleted_at != null) {
                $data->forceDelete();
            } else {
                $data->delete();
            }

            Helpers::registerSystemLogs($request, $id, true, 'Success in delete ' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json([
                'data' => $data,
                'logs' => Helpers::registerPullOutLogs($data->id, $request->user->id, 'Destroy'),
                'msg' => 'Request successfully deleted.',
                Response::HTTP_OK
            ]);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);

        }
    }

    public function sections(Request $request)
    {
        try {
            $user = $request->user;
            $assigned_area = $user->assignedArea->findDetails();

            return response()->json(['data' => Helpers::DivisionAreas($assigned_area)], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'sections', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function sectionEmployees(Request $request) // find all employee of specific section
    {
        try {
            $new_employee_list = collect(); // Initialize as empty collection
            $sector_id = $request->section_id;
            $sector = $request->sector;

            switch ($sector) {
                case 'Division':
                    $assign_areas = AssignArea::where('division_id', $sector_id)
                        ->whereNotIn('employee_profile_id', [$request->user->id])
                        ->get();
                    break;
                case 'Department':
                    $assign_areas = AssignArea::where('department_id', $sector_id)
                        ->whereNotIn('employee_profile_id', [$request->user->id])
                        ->get();
                    break;
                case 'Section':
                    $assign_areas = AssignArea::where('section_id', $sector_id)
                        ->whereNotIn('employee_profile_id', [$request->user->id])
                        ->get();
                    break;
                case 'Unit':
                    $assign_areas = AssignArea::where('unit_id', $sector_id)
                        ->whereNotIn('employee_profile_id', [$request->user->id])
                        ->get();
                    break;
            }

            $new_employee_list = $new_employee_list->merge($assign_areas->pluck('employeeProfile'));

            $data = $new_employee_list->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name() // Assuming you have a name method in your employeeProfile model
                ];
            });

            return response()->json(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'sectionEmployee', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
