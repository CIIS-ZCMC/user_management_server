<?php

namespace App\Http\Controllers\DTR;

use App\Helpers\Helpers;
use Illuminate\Http\Request;
use  App\Models\Biometrics;
use App\Methods\BioControl;
use App\Http\Controllers\DTR\BioMSController;
use App\Http\Controllers\DTR\MailController;
use App\Models\Devices;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use App\Models\DefaultPassword;
use App\Models\EmployeeProfile;


class BioController extends Controller
{
    protected $device;
    protected $device_ids;
    protected $ip_registration;
    protected $devices;
    protected $bioms;

    protected $mailer;

    private $CONTROLLER_NAME = "BioController";

    public function __construct()
    {
        $this->device = new BioControl();
        $this->bioms = new BioMSController();
        $this->device_ids = [
            2
        ];
        $this->ip_registration = json_decode($this->bioms->registrationDevice()->getContent(), true)['data'];
        $this->mailer = new MailController();
    }

    /* ----------------------------- THIS IS FOR REGISTRATION OF BIOMETRICS----------------------------------- */
    public function registerBio(Request $request)
    {
        try {

            $user = $request->user;

            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }


            $biometric_id = $request->biometric_id;
            $name = $request->name;
            $privilege = $request->privilege;
            /* The IP of this option must be the registration device. */
            $ipreg = [];

            if (isset($this->ip_registration[0])) {
                $ipreg = $this->ip_registration[0];
            }


            $bio = Biometrics::where('biometric_id', $biometric_id);


            if (count($bio->get()) == 0) {
                $save = $bio->create([
                    'biometric_id' => $biometric_id,
                    'name' => $name,
                    'privilege' => 0,
                    'biometric' => "NOT_YET_REGISTERED"
                ]);

                if ($save) {
                    $this->device->fetchdatatoDeviceforNewFPRegistration(
                        $ipreg,
                        $biometric_id,
                        $name

                    );
                }

                return response()->json(['message' =>
                'User has been registered successfully, Please proceed to Device to register the Fingerprint']);
            }
            return response()->json(['message' => 'User has already been registered!']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'registerBio', $th->getMessage());
        }
    }

    /**
     * Summary of registerBiometricDevice [VERSION2]
     * 
     * Register biometric device
     * 
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function registerBiometricDevice(Request $request)
    {
        $device_name = $request->device_name;
        $ip_address = $request->ip_address;
        $com_key = 0;
        $soap_port = 80;
        $udp_port = 4370;
        $serial_number = $request->serial_number;
        $mac_address = $request->mac_address;

        $device_exist = Devices::where('ip_address', $ip_address)->where('device_name', $device_name)->first();

        if($device_exist){
            return response()->json(['message' => "Device already exist or IP already in used."], Response::HTTP_BAD_REQUEST);
        }

        $new_biometric = Devices::create([
            'device_name' => $device_name,
            'ip_address' => $ip_address,
            'com_key' => $com_key,
            'soap_port' => $soap_port,
            'udp_port' => $udp_port,
            'serial_number' => $serial_number,
            'mac_address' => $mac_address
        ]);

        return response()->json([
            'data' => $new_biometric,
            'message' => "Successfully registered biometric device."
        ], \Symfony\Component\HttpFoundation\Response::HTTP_CREATED);
    }

    /**
     * Summary of composeNameWithBiometricIDAndUpdateBiometric [VERSION2]
     * 
     * This will modify the existing biometric record to have a second name
     * that will be use for phase2 of testing
     * 
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function composeNameWithBiometricIDAndUpdateBiometric(Request $request)
    {
        DB::beginTransaction();

        $biometrics = Biometrics::whereNull('name_with_biometric')->get();

        try{
            foreach($biometrics as $biometric){
                $employee = EmployeeProfile::where('biometric_id', $biometric->biometric_id)->first();

                if(!$employee){
                    continue;
                }

                $personal_information = $employee->personalInformation;
                $name_with_biometric = $employee->employee_id.'-'.$personal_information->last_name;
                $biometric->update(['name_with_biometric' => $name_with_biometric]);
            }
        }catch(\Throwable $th){
            DB::rollBack();
            return response()->json([
                'data' => $th->getMessage(),
                'message' => "Failed to patch records."
            ], \Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        DB::commit();

        return response()->json([
            'data' => Biometrics::all(),
            'message' => "Successfully update records"
        ], \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }

    /**
     * Summary of checkUserDataByBiometricID [VERSION2]
     * 
     * for troubleshooting when checking device user records
     * 
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function checkDeviceUserRecords(Request $request)
    {
        $biometric_id = $request->biometric_id;
        $device_name = $request->device_name;

        $device = Devices::where('device_name', operator: $device_name)->first();

        if(!$device){
            return response()->json(['message' => "Device not found."], \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
        }

        $result = $this->device->checkDeviceUserRecords($device);

        if(count($result['data']) === 0){
            return response()->json(['message' => $result['message']], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
        }

        return response()->json($result, \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }

    /**
     * Summary of checkUserDataByBiometricID [VERSION2]
     * 
     * for troubleshooting when checking if user biometric details is registered in
     * a target device.
     * 
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function checkUserBiometricDetailsFromDevice(Request $request)
    {
        $biometric_id = $request->query('biometric_id');
        $device_id = $request->query('device_id');

        $device = Devices::find($device_id);

        if(!$device){
            return response()->json(['message' => "Device not found."], \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
        }

        $result = $this->device->connectAndRetrieveUserDetailsFromDevice($device, $biometric_id);

        if(count($result['data']) === 0){
            return response()->json(['message' => $result['message']], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
        }

        return response()->json($result, \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }

    /**
     * Summary of extractFieldsFromJsonWithGivenFieldKey [VERSION2]
     * 
     * @param mixed $device_user_records
     * @param mixed $targetField
     * @return array
     */
    private function extractFieldsFromJsonWithGivenFieldKey($device_user_records, $targetField)
    {
        $extracted_data = array_column($device_user_records, $targetField);

        return $extracted_data;
    }

    /**
     * Summary of countNumberNameFormatEntries
     * 
     * This will check the total number of entries with proper name format
     * @param array $data
     * @return int
     */
    private function countNumberNameFormatEntries(array $data): int
    {
        $count = 0;
        
        foreach ($data as $entry) {
            if (isset($entry['Name']) && is_string($entry['Name'])) {
                $name = trim($entry['Name']);
                
                // Updated pattern to handle:
                // 1. Numbers at start
                // 2. Multiple hyphens
                // 3. Special characters (ñ, etc.)
                // 4. Apostrophes and other name characters
                if (preg_match('/^\d+(-\s*[\p{L}\s\'\-]+)+$/u', $name)) {
                    $count++;
                } 
                // else {
                //     // For debugging:
                //     echo "Non-matching name: '$name'\n";
                // }
            }
        }
        
        return $count;
    }

    private function retrieveTotalBiometricRecords()
    {
        return Biometrics::whereNotNull('name_with_biometric')
            ->whereNot('biometric', 'NOT_YET_REGISTERED')
            ->whereExists(function($query) {
                $query->select(DB::raw(1))
                    ->from('employee_profiles')
                    ->whereColumn('employee_profiles.biometric_id', 'biometrics.biometric_id')
                    ->whereNotNull('employee_profiles.employee_id');
            })
            ->count();
    }

    /**
     * Summary of populateBiometricDeviceWithoutOveridingExistingRecords [VERSION2]
     * 
     * Check if device exist, then check if it has existing user details registered
     * and populate the device with non-existing record
     * 
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function populateBiometricDeviceWithoutOveridingExistingRecords(Request $request)
    {
        try {
            $start = microtime(true);
            $device_id = $request->query('device_id');
            $biometric_device = Devices::where('id', operator: $device_id)->first();
            $extracted_user_names = [];
            
            $existing_users_of_the_device = $this->device->checkDeviceUserRecords($biometric_device);

            if(count($existing_users_of_the_device['data']) > 0){
                $extracted_user_names = $this->extractFieldsFromJsonWithGivenFieldKey($existing_users_of_the_device['data'], 'Name');
            }

            $result = $this->device->populateBiometricDeviceWithEmployeesBiometricRecord($biometric_device, $extracted_user_names);

            $latest_device_record = $this->device->checkDeviceUserRecords($biometric_device);
            $total_success_entries = $this->countNumberNameFormatEntries($latest_device_record['data']);
            $total_biometric_records = $this->retrieveTotalBiometricRecords();

            // return response()->json(['message' => $this->$total_success_entries], 500);

            return response()->json([
                'data' => $result,
                'message' => 'Successfully populate device.',
                'meta' => [
                    "total_success_entries" => $total_success_entries,
                    "total_biometric_records" => $total_biometric_records,
                    "percentage_uploaded" => number_format(($total_success_entries / $total_biometric_records) * 100, 2) . "%",
                    "device_name" => $biometric_device->device_name,
                    "duration(milliseconds)" => round((microtime(true) - $start) * 1000),
                ]
            ], \Symfony\Component\HttpFoundation\Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'populateBiometricDeviceWithoutOveridingExistingRecords', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function fetchUserFromDevice(Request $request)
    {
        try {
            $biometric_id = $request->biometricIDs;
            $dvc = [];

            if (isset($this->ip_registration[0])) {
                $dvc = $this->ip_registration[0];
            }

            if (!$dvc) {
                return response()->json(['message' => 'Failed to pull data']);
            }
            
            foreach ($biometric_id as $key => $value) {
                $this->device->fetchUserDataFromDeviceToDB($dvc, $value);
                if ($this->device->fetchUserDataFromDeviceToDB($dvc, $value)) {
                    if ($this->device->validateTemplate($dvc, $value)) {
                        $this->device->deleteDataFromDevice($dvc, $value); //DELETE USER INFO , IF FINGERPRINT DETECTED
                    }
                }
            }

            return response()->json(['message' => 'User Data from Device has been pulled successfully!']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'fetchUserFromDevice', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function fetchUserToDevice(Request $request)
    {
        try {
            $biometric_id = $request->biometricIDs;

            $dvc = [];

            if (isset($this->ip_registration[0])) {
                $dvc = $this->ip_registration[0];
            }

            if (!$dvc) {
                return response()->json(['message' => 'Failed to push data']);
            }
            foreach ($biometric_id as $key => $value) {
                $this->device->fetchUserDataFromDBToDevice($dvc, $value);
            }

            return response()->json(['message' => 'User Data fetched to device successfully!']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'fetchUserToDevice', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    /* ------------------------------------------------------------------------------------------- */

    /*
    TO DO --- ip address
    get all the device ID for this function to apply in each devices
    */

    // Push user biometric records to biometric device (Fingerprint)
    public function fetchBIOToDevice()
    {
        try {
          
            $devices = Devices::where('id', 1)->get();
           
            foreach ($devices as $dv) {
                // $bios = Devices::where('id', $dv)->get();
             return   $this->device->fetchAllDataToDevice($dv);
            }

            return response()->json(['message' => 'User Data has been fetched to device successfully']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'fetchBIOToDevice', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }


    public function fetchUserToOPDevice(Request $request)
    {
        try {
            $biometricIDs = $request->biometricIDs;
            $devices = Devices::where('is_registration', 0)->get();
            foreach ($devices as $dv) {
                $this->device->fetchSpecificDataToDevice($dv, $biometricIDs);
            }
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'fetchUserToOPDevice', $th->getMessage());
            return $th;
        }
    }

    public function setUserSuperAdmin(Request $request)
    {
        try {
            $biometric_id = $request->biometricIDs;
            $unset = $request->unset;
            $devices = Devices::where('is_registration', 0)->get();


            foreach ($devices as $dv) {
                $this->device->setSuperAdmin($dv, $biometric_id, $unset);
            }
            // return response()->json(['message' => 'Settings saved successfully!']);
            // return response()->json(['message' => 'No device found']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'setUserSuperAdmin', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteSpecificBIOFromDevice(Request $request)
    {
        try {
            $biometric_id = $request->biometricIDs;

            $devices = Devices::where('is_registration', 0)->get();
            foreach ($devices as $dv) {

                foreach ($biometric_id as $key => $value) {
                    $this->device->deleteDataFromDevice($dv, $value);
                }
            }
            return response()->json(['message' => 'User data from this device has been deleted successfully']);
            // return response()->json(['message' => 'No device found']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'deleteSpecificBIOFromDevice', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function deleteAllBIOFromDevice()
    {
        try {
            foreach ($this->device_ids as $dv) {
                $bios = Devices::where('id', $dv)->get();

                if (count($bios) >= 1) {
                    $this->device->deleteAllDataFromDevice($bios[0]);
                    return response()->json(['message' => 'All data from device has been deleted successfully']);
                }
            }
            return response()->json(['message' => 'No device found']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'deleteAllBIOFromDevice', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function syncTime(Request $request)
    {
        try {
            $this->device_ids = $request->deviceID;

            foreach ($this->device_ids as $dv) {
                $bios = Devices::where('id', $dv)->get();
                $this->device->setDeviceDateAndTime($bios[0]);
            }
            return response()->json(['message' => 'Date and Time Synced Successfully!']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'syncTime', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function enableORDisable(Request $request)
    {

        try {
            $type_of_action = $request->TypeofAction;
            foreach ($this->device_ids as $dv) {
                $bios = Devices::where('id', $dv)->get();
                $this->device->deviceEnableORDisable($bios[0], $type_of_action);
            }
            return response()->json(['message' => 'Settings Set Successfully']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'enableORDisable', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restartORShutdown(Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $this->device_ids = $request->deviceID;
            $type_of_action = $request->TypeofAction;
            foreach ($this->device_ids as $dv) {
                $bios = Devices::where('id', $dv)->get();
                $this->device->deviceShutdownORrestart($bios[0], $type_of_action);
            }

            return response()->json(['message' => 'Device exiting...']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'restartORShutdown', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function setTime(Request $request)
    {
        try {
            $this->device_ids = $request->deviceID;
            $time = $request->time;
            foreach ($this->device_ids as $dv) {
                $bios = Devices::where('id', $dv)->get();
                $this->device->setCustomDeviceDateAndTime($bios[0], $time);
            }
            return response()->json(['message' => 'Date and Time Synced Successfully!']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'setTime', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
