<?php

namespace App\Http\Controllers\DTR;

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

        $user = $request->user;

        $password_decrypted = Crypt::decryptString($user['password_encrypted']);
        $password = strip_tags($request->password);
        if (!Hash::check($password . env("SALT_VALUE"), $password_decrypted)) {
            return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
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
                $defpassword = DefaultPassword::first()->password;
                $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();

                $credential = new Request([
                    'EmployeeID' => $employee->employee_id,
                    'Email' => $employee->personalInformation->contact->email_address,
                    'Receiver' => $employee->name(),
                    'Password' => $defpassword
                ]);

                $this->mailer->sendCredentials($credential);
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
                if ($this->device->fetchUserDataFromDeviceToDB($dvc, $value)) {
                    if ($this->device->validateTemplate($dvc, $value)) {
                        $this->device->deleteDataFromDevice($dvc, $value); //DELETE USER INFO , IF FINGERPRINT DETECTED
                    }
                }
            }


            return response()->json(['message' => 'User Data from Device has been pulled successfully!']);
        } catch (\Throwable $th) {

            return response()->json(['message' =>  $th->getMessage()]);
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

            $devices = Devices::where('is_registration', 0)->get();

            foreach ($devices as $dv) {
                // $bios = Devices::where('id', $dv)->get();
                $this->device->fetchAllDataToDevice($dv);
            }
            return response()->json(['message' => 'User Data has been fetched to device successfully']);
        } catch (\Throwable $th) {
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
            return $th;
            return response()->json(['message' =>  $th->getMessage()]);
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

            return response()->json(['message' =>  $th->getMessage()]);
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
            return response()->json(['message' =>  $th->getMessage()]);
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
            $user = $request->user;
            $password_decrypted = Crypt::decryptString($user['password_encrypted']);
            $password = strip_tags($request->password);
            if (!Hash::check($password . env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $this->device_ids = $request->deviceID;
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
            $this->device_ids = $request->deviceID;
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
