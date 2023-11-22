<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use  App\Models\Biometrics;
use App\Methods\BioControl;
use App\Http\Controllers\DTR\BioMSController;
use App\Models\Devices;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;


class BioController extends Controller
{
    protected $device;
    protected $device_ids;
    protected $ip_registration;
    protected $devices;
    protected $bioms;
    public function __construct()
    {
        $this->device = new BioControl();
        $this->bioms = new BioMSController();
        $this->device_ids = [
            1
        ];
        $this->ip_registration = json_decode($this->bioms->registrationDevice()->getContent(), true)['data'];
    }

    /* ----------------------------- THIS IS FOR REGISTRATION OF BIOMETRICS----------------------------------- */
    public function registerBio(Request $request)
    {
        $biometric_id = $request->biometric_id;
        $name = $request->name;
        $privilege = $request->privilege;
        /* The IP of this option must be the registration device. */

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
                    $this->ip_registration[0],
                    $biometric_id,
                    $name

                );
            }

            return response()->json(['message' =>
            'User has been registered successfully, Please proceed to Device to register the Fingerprint']);
        }
        return response()->json(['message' => 'User has already been registered!']);
    }

    public function fetchUserFromDevice(Request $request)
    {
        try {
            $biometric_id = $request->biometric_id;
            $dvc = $this->ip_registration[0];

            if ($this->device->fetchUserDataFromDeviceToDB($dvc, $biometric_id)) {
                if ($this->device->validateTemplate($dvc, $biometric_id)) {
                    $this->device->deleteDataFromDevice($dvc, $biometric_id); //DELETE USER INFO , IF FINGERPRINT DETECTED
                }
                return response()->json(['message' => 'User Data from Device has been pulled successfully!']);
            }
            return response()->json(['message' => 'Failed to pull data']);
        } catch (\Throwable $th) {

            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function fetchUserToDevice(Request $request)
    {
        try {
            $biometric_id = $request->biometric_id;
            $dvc = $this->ip_registration[0];
            if ($this->device->fetchUserDataFromDBToDevice($dvc, $biometric_id)) {
                return response()->json(['message' => 'User Data fetched to device successfully!']);
            }
            return response()->json(['message' => 'Failed to push data']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }


    /* ------------------------------------------------------------------------------------------- */

    /* 
    TO DO --- ip address
    get all the device ID for this function to apply in each devices
    */
    public function fetchBIOToDevice()
    {
        try {
            foreach ($this->device_ids as $dv) {
                $bios = Devices::where('id', $dv)->get();
                $this->device->fetchAllDataToDevice($bios[0]);
            }
            return response()->json(['message' => 'User Data has been fetched to device successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function setUserSuperAdmin(Request $request)
    {
        try {
            $biometric_id = $request->biometric_id;
            $unset = $request->unset;
            foreach ($this->device_ids as $dv) {
                $bios = Devices::where('id', $dv)->get();
                $this->device->setSuperAdmin($bios[0], $biometric_id, $unset);
            }
            return response()->json(['message' => 'Settings saved successfully!']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function deleteSpecificBIOFromDevice(Request $request)
    {
        try {
            $biometric_id = $request->biometric_id;
            foreach ($this->device_ids as $dv) {
                $bios = Devices::where('id', $dv)->get();
                $this->device->deleteDataFromDevice($bios[0], $biometric_id);
            }
            return response()->json(['message' => 'User data from this device has been deleted successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }


    public function deleteAllBIOFromDevice()
    {
        try {
            foreach ($this->device_ids as $dv) {
                $bios = Devices::where('id', $dv)->get();
                $this->device->deleteAllDataFromDevice($bios[0]);
            }
            return response()->json(['message' => 'All data from device has been deleted successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }


    public function syncTime()
    {
        try {

            foreach ($this->device_ids as $dv) {
                $bios = Devices::where('id', $dv)->get();
                $this->device->setDeviceDateAndTime($bios[0]);
            }
            return response()->json(['message' => 'Date and Time Synced Successfully!']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
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
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function restartORShutdown(Request $request)
    {
        try {
            $type_of_action = $request->TypeofAction;
            foreach ($this->device_ids as $dv) {
                $bios = Devices::where('id', $dv)->get();
                $this->device->deviceShutdownORrestart($bios[0], $type_of_action);
            }

            return response()->json(['message' => 'Device exiting...']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function setTime(Request $request)
    {
        try {
            $time = $request->time;
            foreach ($this->device_ids as $dv) {
                $bios = Devices::where('id', $dv)->get();
                $this->device->setCustomDeviceDateAndTime($bios[0], $time);
            }
            return response()->json(['message' => 'Date and Time Synced Successfully!']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }
}
