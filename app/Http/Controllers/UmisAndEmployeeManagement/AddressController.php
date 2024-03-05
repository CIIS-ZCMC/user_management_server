<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AddressManyRequest;
use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Models\EmployeeProfile;

class AddressController extends Controller
{
    private $CONTROLLER_NAME = 'Address';
    private $PLURAL_MODULE_NAME = 'addresses';
    private $SINGULAR_MODULE_NAME = 'address';

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $addresses = Address::where('personal_information_id', $id)->get();

            if(count($addresses) === 0)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => AddressResource::collection($addresses),
                'message' => 'Address records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID($id, Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $personal_information = $employee_profile->personalInformation;
            $addresses = $personal_information->addresses;

            if(count($addresses) === 0)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => AddressResource::collection($addresses),
                'message' => 'Employee address records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(Request $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (is_bool($value) || $value === null) {
                    $cleanData[$key] = $value;
                } else {
                    $cleanData[$key] = strip_tags($value);
                }
            }

            $address = Address::create($cleanData);

            Helpers::registerSystemLogs($request, $address['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new AddressResource($address),
                'message' => 'New employee address added.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $address = Address::findOrFail($id);

            if(!$address)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new AddressResource($address),
                'message' => 'Employee address retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, AddressRequest $request)
    {
        try{
            $address = Address::find($id);

            if(!$address)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (is_bool($value) || $value === null) {
                    $cleanData[$key] = $value;
                } else {
                    $cleanData[$key] = strip_tags($value);
                }
            }

            $address->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new AddressResource($address),
                'message' => 'Employee address detail updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // API [address-many/{id}]
    public function updateMany($id, AddressManyRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->address as $key => $address) {
                $new_clean_data = [];
                foreach($address as $key => $value){
                    if (is_bool($value) || $value === null) {
                        $new_clean_data[$key] = $value;
                        continue;
                    }
                    $new_clean_data[$key] = strip_tags($value);
                }
                $cleanData[] = $new_clean_data;
            }

            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile){
                return response()->json(['message' => "No employee existing."], Response::HTTP_NOT_FOUND);
            }

            if(count($employee_profile->personalInformation->addresses) === 2){
                $addresses = $employee_profile->personalInformation->addresses;

                /**
                 * If employee has existing 2 address registered and the update only 1
                 * as permanent address and residential
                 * This will update the 1 address of the employee and delete the other one.
                 */
                if($cleanData['is_permanent'] === 1){
                    $index = 0;
                    $updated_address = [];

                    foreach($addresses as $address){
                        if($index === 0){
                            $address->update(...$cleanData[0]);
                            $updated_address[] = $address;
                            $updated_address[] = $address;
                            continue;
                        }
                        $address->delete();
                    }
                    
                    return response()->json([
                        'data' => AddressResource::collection($updated_address),
                        'message' => 'Employee address detail updated.'
                    ], Response::HTTP_OK);
                }
                
                /**
                 * Updating existing addresses
                 */
                $updated_address = [];

                foreach($addresses as $address){
                    foreach($cleanData as $value){
                        $address->update(...$value);
                        $updated_address[] = $address;
                    }
                }

                return response()->json([
                    'data' => AddressResource::collection($updated_address),
                    'message' => 'Employee address detail updated.'
                ], Response::HTTP_OK);
            }

            /**
             * If the new update is also permanent
             * this will update only the existing permanent address
             * and return as both residential and permanent
             */
            if($cleanData['is_permanent'] === 0){
                $address = Address::where('personal_information_id', $employee_profile->personalInformation->id)
                    ->first()->update(...$cleanData[0]);
                
                return response()->json([
                    'data' => AddressResource::collection([$address, $address]),
                    'message' => 'Employee address detail updated.'
                ], Response::HTTP_OK);
            }   

            /**
             * If employee has 1 previous address and the update will be 1 permanent and 1 residential
             * this will update the existing address
             * and register new address
             */
            $existing_address = Address::where('personal_information_id', $employee_profile->personalInformation->id)
                ->first()->update(...$cleanData[0]);

            $new_address = Address::create(...$cleanData[1]);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => AddressResource::collection([$existing_address, $new_address]),
                'message' => 'Employee address detail updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $address = Address::findOrFail($id);

            if(!$address)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $address->delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee address deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByPersonalInformationID($id, Request $request)
    {
        try{
            $addresses = Address::where('personal_information_id', $id)->get();

            if(count($addresses) === 0)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($addresses as $key => $address){
                $address->delete();
            }
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee address records deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByEmployeeID($id, AuthPinApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee_profile->personalInformation;
            $addresses = $personal_information->addresses;

            if(count($addresses) === 0)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($addresses as $key => $address){
                $address->delete();
            }
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee address records deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
