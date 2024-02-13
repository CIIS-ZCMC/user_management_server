<?php

namespace App\Http\Controllers\Schedule;

use App\Models\PullOut;
use App\Models\EmployeeProfile;
use App\Models\Section;

use App\Http\Resources\PullOutResource;
use App\Http\Requests\PullOutRequest;
use App\Helpers\Helpers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;

use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            $data = PullOut::where('employee_profile_id ', $user->id)->get();
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

                if ($employeeArea) {
                    switch ($employeeArea['sector']) {
                        case 'Division':
                            $approving_officer = $employee->assignedArea->division->chief_employee_profile_id;
                            break;

                        case 'Department':
                            $approving_officer = $employee->assignedArea->department->head_employee_profile_id;
                            break;

                        case 'Section':
                            $section = Section::find($employeeArea['details']->id);
                            if ($section->division !== null) {
                                $approving_officer = $section->division->chief_employee_profile_id;
                            }

                            $approving_officer = $employee->assignedArea->section->supervisor_employee_profile_id;
                            break;

                        case 'Unit':
                            $approving_officer = $employee->assignedArea->department->head_employee_profile_id ;
                            break;

                        default:
                            return null;
                    }
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
            return response()->json(['data' => new PullOutResource($data),
                                    'logs' => Helpers::registerPullOutLogs($data->id, $user->id, 'Store'),
                                    'msg' => 'Pull out requested'], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
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

            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $status = null;
            if ($request->approval_status === 'approved') {
                switch ($data->status) {
                    case 'applied':
                        $status = 'approved';
                        break;

                    case 'declined':
                        $status = 'declined';

                    default:
                        $status = 'approved';
                        break;
                }
            } else if ($request->approval_status === 'declined') {
                $status = 'declined';   
            }

            $data->update(['status' => $status, 'remarks' => $request->remarks, 'approval_date' => Carbon::now()]);

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in updating.' . $this->SINGULAR_MODULE_NAME . '.');
            return response()->json(['data' => new PullOutResource($data),
                                    'logs' => Helpers::registerPullOutLogs($data->id, $user->id, $status),
                                    'msg' => 'Pull out is '.$status], Response::HTTP_OK);

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
            return response()->json(['data' => $data,
                                    'logs' => Helpers::registerPullOutLogs($data->id, $request->user->id,'Destroy'),
                                    'msg' => 'Request successfully deleted.', Response::HTTP_OK]);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);

        }
    }
}
