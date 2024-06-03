<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Services\RequestLogger;
use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\EmployeeProfile;

class ContactController extends Controller
{
    private $CONTROLLER_NAME = 'Contact';
    private $PLURAL_MODULE_NAME = 'contacts';
    private $SINGULAR_MODULE_NAME = 'contact';

    public function findByPersonalInformationID($id, Request $request)
    {
        try {
            $contact = Contact::where('personal_information_id', $id)->first();

            if (!$contact) {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new ContactResource($contact),
                'message' => 'Contact details retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeeContact', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID($id, Request $request)
    {
        try {
            $contact = EmployeeProfile::find($id);

            if (!$contact) {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new ContactResource($contact),
                'message' => 'Contact detail retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeeContact', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store($personal_information_id, ContactRequest $request)
    {
        try {
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $cleanData['personal_information_id'] = $personal_information_id;
            $contact = Contact::create($cleanData);

            return $contact;
        } catch (\Throwable $th) {
            throw new \Exception("Failed to register employee contact.", 400);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $contact = Contact::find($id);

            if (!$contact) {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new ContactResource($contact)], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, Request $request)
    {
        try {

            if (isset($request->password)) {
                $user = $request->user;
                $cleanData['pin'] = strip_tags($request->password);

                if ($user['authorization_pin'] !==  $cleanData['pin']) {
                    return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
                }
            }
            
            $contact = Contact::where('personal_information_id', $id)->first();

            if (!$contact) {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];


            
            if (isset($request->password)) {
                foreach ($request->contact as $key => $value) {
                    if ($value === null || $key === 'password') {
                        $cleanData[$key] = $value;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($value);
                }
            } 

            foreach ($request->all() as $key => $value) {
                if ($value === null || $key === 'password') {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $contact->update($cleanData);

            return $contact;
        } catch (\Throwable $th) {
            throw new \Exception("Failed to register employee contact.", 400);
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

            $contact = Contact::findOrFail($id);

            if (!$contact) {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $contact->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Employee contact record deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroyByPersonalInformation($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $contact = Contact::where("personal_information_id", $id)->first();

            if (!$contact) {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $contact->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Employee contact record deleted.'], Response::HTTP_OK);
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

            $employee = EmployeeProfile::find($id);

            if (!$employee) {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee->personalInformation;

            $contact = $personal_information->contact;
            $contact->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Employee contact record deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
