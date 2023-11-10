<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use  App\Models\biometrics;
use App\Methods\Bio_contr;
use App\Http\Controllers\DTR\BioMSController;
use App\Models\devices;
use App\Http\Controllers\Controller;

class BioController extends Controller
{
    protected $Device;
    protected $device_ids;
    protected $ip_registration;
    protected $devices;
    protected $bioms;
    public function __construct()
    {
        $this->Device = new Bio_contr();
        $this->bioms = new BioMSController();
        $this->device_ids = [
            1
        ];
        $this->ip_registration = json_decode($this->bioms->registration_device()->getContent(), true)['data'];
    }

    /* ----------------------------- THIS IS FOR REGISTRATION OF BIOMETRICS----------------------------------- */


    public function Register_Bio(Request $request)
    {
        $biometric_id = $request->biometric_id;
        $name = $request->name;
        $privilege = $request->privilege;
        /* The IP of this option must be the registration device. */

        $bio = biometrics::where('biometric_id', $biometric_id);

        if (count($bio->get()) == 0) {
            $save = $bio->create([
                'biometric_id' => $biometric_id,
                'name' => $name,
                'privilege' => 0,
                'biometric' => "NOT_YET_REGISTERED"
            ]);

            if ($save) {
                $this->Device->FetchdatatoDevice_for_New_FP_Registration(
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





    public function Fetch_User_FromDevice(Request $request)
    {
        try {
            $biometric_id = $request->biometric_id;
            $dvc = $this->ip_registration[0];

            if ($this->Device->FetchUser_datafromdevice_toDB($dvc, $biometric_id)) {
                if ($this->Device->ValidateTemplate($dvc, $biometric_id)) {
                    $this->Device->Delete_datafromdevice($dvc, $biometric_id); //DELETE USER INFO , IF FINGERPRINT DETECTED
                }
                return response()->json(['message' => 'User Data from Device has been pulled successfully!']);
            }
            return response()->json(['message' => 'Failed to pull data']);
        } catch (\Throwable $th) {

            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function Fetch_User_ToDevice(Request $request)
    {
        try {
            $biometric_id = $request->biometric_id;
            $dvc = $this->ip_registration[0];
            if ($this->Device->FetchUser_datafromDB_toDevice($dvc, $biometric_id)) {
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
    public function Fetch_BIO_To_Device()
    {
        try {
            foreach ($this->device_ids as $dv) {
                $bios = devices::where('id', $dv)->get();
                $this->Device->Fetchall_data_to_device($bios[0]);
            }
            return response()->json(['message' => 'User Data has been fetched to device successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function Set_User_SuperAdmin(Request $request)
    {
        try {
            $biometric_id = $request->biometric_id;
            $unset = $request->unset;
            foreach ($this->device_ids as $dv) {
                $bios = devices::where('id', $dv)->get();
                $this->Device->Set_SuperAdmin($bios[0], $biometric_id, $unset);
            }
            return response()->json(['message' => 'Settings saved successfully!']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function Delete_SpecificBIO_From_Device(Request $request)
    {
        try {
            $biometric_id = $request->biometric_id;
            foreach ($this->device_ids as $dv) {
                $bios = devices::where('id', $dv)->get();
                $this->Device->Delete_datafromdevice($bios[0], $biometric_id);
            }
            return response()->json(['message' => 'User data from this device has been deleted successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }


    public function Delete_AllBIO_From_Device()
    {
        try {
            foreach ($this->device_ids as $dv) {
                $bios = devices::where('id', $dv)->get();
                $this->Device->DeleteAll_datafromdevice($bios[0]);
            }
            return response()->json(['message' => 'All data from device has been deleted successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }


    public function SyncTime()
    {
        try {

            foreach ($this->device_ids as $dv) {
                $bios = devices::where('id', $dv)->get();
                $this->Device->Set_device_dateandtime($bios[0]);
            }
            return response()->json(['message' => 'Date and Time Synced Successfully!']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function Enable_OR_Disable(Request $request)
    {
        try {
            $TypeofAction = $request->TypeofAction;
            foreach ($this->device_ids as $dv) {
                $bios = devices::where('id', $dv)->get();
                $this->Device->Device_Enable_OR_Disable($bios[0], $TypeofAction);
            }
            return response()->json(['message' => 'Settings Set Successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function Restart_OR_Shutdown(Request $request)
    {
        try {
            $TypeofAction = $request->TypeofAction;
            foreach ($this->device_ids as $dv) {
                $bios = devices::where('id', $dv)->get();
                $this->Device->Device_Shutdown_OR_restart($bios[0], $TypeofAction);
            }

            return response()->json(['message' => 'Device exiting...']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function settime(Request $request)
    {
        try {
            $time = $request->time;
            foreach ($this->device_ids as $dv) {
                $bios = devices::where('id', $dv)->get();
                $this->Device->Set_CustomDevice_dateandtime($bios[0], $time);
            }
            return response()->json(['message' => 'Date and Time Synced Successfully!']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }
}
