<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\Helpers;
use App\Http\Requests\IdentificationNumberRequest;
use App\Http\Resources\IdentificationNumberResource;
use App\Models\IdentificationNumber;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\Crypt;

class IdentificationNumberController extends Controller
{
    private $CONTROLLER_NAME = 'Identification Number';
    private $PLURAL_MODULE_NAME = 'divisions';
    private $SINGULAR_MODULE_NAME = 'division';

    public function findByPersonalInformationID($id, Request $request)
    {
        try {
            $identification = IdentificationNumber::where('personal_information_id', $id)->first();

            if (!$identification) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new IdentificationNumberResource($identification), 'message' => 'Employee identification number retrieved.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID($id, Request $request)
    {
        try {
            $employee_profile = EmployeeProfile::find($id);

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee_profile->personalInformation;
            $identification = $personal_information->identification;

            return response()->json(['data' => new IdentificationNumberResource($identification), 'message' => 'Employee identification number retrieved.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store($personal_information_id, $identification)
    {
        try {
            $cleanData = [];

            foreach ($identification as $key => $value) {
                if ($value === 'null' || $value === null || $key === 'personal_information_id') {
                    $cleanData[$key] = $value;
                    continue;
                }
                try {
                    $cleanData[$key] =  $this->encryptData(strip_tags($value));
                } catch (\Throwable $th) {
                    $cleanData[$key] = $value;
                }
            }
            $cleanData['personal_information_id'] = $personal_information_id;

            $identification = IdentificationNumber::create($cleanData);

            return $identification;
        } catch (\Throwable $th) {
            throw new \Exception("Failed to register employee identifications number.", 400);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $identification = IdentificationNumber::find($id);

            if (!$identification) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new IdentificationNumberResource($identification), 'message' => 'Identification number record retrieved.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, IdentificationNumberRequest $request)
    {
        try {
            $identification = IdentificationNumber::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null || $key === 'personal_information_id') {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] =  $this->encryptData(strip_tags($value));
            }

            $identification->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['data' => new IdentificationNumberResource($identification), "message" => 'Employee Identification number updated.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $identification = IdentificationNumber::findOrFail($id);

            if (!$identification) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $identification->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Employee identification number record Deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroyByPersonalInformation($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $identification = IdentificationNumber::where('personal_information_id', $id)->first();

            if (!$identification) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $identification->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Employee identification number record Deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroyByEmployeeID($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $employee_profile = EmployeeProfile::find($id);

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee_profile->personalInformation;
            $identification = $personal_information->identification;
            $identification->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Employee identification number record Deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function encryptData($dataToEncrypt)
    {
        return Crypt::encrypt($dataToEncrypt);
    }
}
