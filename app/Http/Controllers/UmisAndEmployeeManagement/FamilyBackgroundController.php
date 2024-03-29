<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Resources\ChildResource;
use App\Models\Child;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\FamilyBackgroundRequest;
use App\Http\Resources\FamilyBackgroundResource;
use App\Models\PersonalInformation;
use App\Models\FamilyBackground;
use App\Models\EmployeeProfile;

class FamilyBackgroundController extends Controller
{
    private $CONTROLLER_NAME = 'Legal Information Question Controller';
    private $PLURAL_MODULE_NAME = 'family backgrounds';
    private $SINGULAR_MODULE_NAME = 'family background';

    public function findByEmployeeID($id, Request $request)
    {
        try {
            $employee = EmployeeProfile::find($id);

            if (!$employee) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee->personalInformation;


            $family_background = FamilyBackground::where('personal_information_id', $personal_information['id'])->first();

            if (!$family_background) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new FamilyBackgroundResource($family_background), 'message' => 'Employee family background record retrieved.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'familyBackGroundEmployee', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByPersonalInformationID($id, Request $request)
    {
        try {
            $family_background = FamilyBackground::where('personal_information_id', $id)->first();

            if (!$family_background) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new FamilyBackgroundResource($family_background), 'message' => 'Employee family background record retrieved.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'familyBackGroundPersonalInformation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Family background registration and Children registration
     */
    public function store(FamilyBackgroundRequest $request)
    {


        try {
            $failed = [];
            $success = [];
            $cleanData = [];

            $personal_information_id = strip_tags($request->input('personal_information_id'));

            $personal_information = PersonalInformation::find($personal_information_id);

            if (!$personal_information) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach ($request->all() as $key => $value) {
                if ($key === 'user' || $key === 'children') continue;
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                if ($key === 'tin_no' || $key === 'rdo_no') {
                    $cleanData[$key] = $this->encryptData($value);
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $family_background = FamilyBackground::create($cleanData);



            foreach (json_decode($request->children) as $child) {
                $child_data = [];
                $child_data['personal_information_id'] = $personal_information_id;
                foreach ($child as $key => $value) {
                    if ($value === null) {
                        $child_data[$key] = $value;
                        continue;
                    }
                    $child_data[$key] = strip_tags($value);
                }
                $child_store = Child::create($child_data);

                if (!$child_store) {
                    $failed[] = $child;
                    continue;
                }

                $success[] = $child_store;
            }

            if (count($failed) > 0) {
                return response()->json([
                    'data' => [
                        'family' => new FamilyBackgroundResource($family_background),
                        'children' => ChildResource::collection($success)
                    ]
                ], Response::HTTP_OK);
            }

            Helpers::registerSystemLogs($request, $family_background['id'], true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json([
                'data' => [
                    'family' => new FamilyBackgroundResource($family_background),
                    'children' => ChildResource::collection($success)
                ],
                'message' => 'New family background registered.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $family_background = FamilyBackground::findOrFail($id);

            if (!$family_background) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new FamilyBackgroundResource($family_background), 'message' => 'Family background record retrieved.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, FamilyBackgroundRequest $request)
    {
        try {
            $family_background = FamilyBackground::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                if ($key === 'tin_no' || $key === 'rdo_no') {
                    $cleanData[$key] = $this->encryptData($value);
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $family_background->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['data' => new FamilyBackgroundResource($family_background), 'message' => 'Employee family background details updated.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $family_background = FamilyBackground::findOrFail($id);

            if (!$family_background) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $family_background->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Employee family background record deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroyByPersonalInformationID($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $family_background = FamilyBackground::where('personal_information_id', $id)->first();

            if (!$family_background) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $family_background->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting employee ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Employee family background record deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroyPersonalInformation', $th->getMessage());
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

            $employee = EmployeeProfile::find($id);

            if (!$employee) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee->personalInformation;

            $family_background = $personal_information->familyBackground;
            $family_background->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting employee ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Employee family background record deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroyEmployee', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function encryptData($dataToEncrypt)
    {
        return Crypt::encrypt($dataToEncrypt);
    }
}
