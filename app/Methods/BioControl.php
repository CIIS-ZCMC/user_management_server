<?php

namespace App\Methods;

use TADPHP\TADFactory;
use  App\Models\Biometrics;
use App\Models\Devices;

class BioControl
{

    public function bIO($device)
    {
        /* Validate if connected to device or not */
        try {
            $options = [
                'ip' => $device['ip_address'],
                'internal_id' => 1,
                'com_key' => $device['com_key'],
                'description' => 'TAD1',
                'soap_port' => $device['soap_port'],
                'udp_port' => $device['udp_port'],
                'encoding' => 'utf-8'
            ];
            $tad_factory = new TADFactory($options);
            $tad = $tad_factory->get_instance();
            if ($tad->get_date()) {
                $getsnmc = json_decode($this->getSNMAC($tad)->getContent(), true);
                Devices::findorFail($device['id'])->update([
                    'serial_number' => $getsnmc['serialnumber'],
                    'mac_address' => $getsnmc['macaddress']
                ]);
                return $tad;
            }
        } catch (\Throwable $th) {
            if (isset($device['id'])) {
                Devices::findorFail($device['id'])->update([
                    'serial_number' => null,
                    'mac_address' => null,
                ]);
            }

            return false;
        }
    }

    public function getSNMAC($tad)
    {
        $sn = $tad->get_serial_number();
        $ma = $tad->get_mac_address();
        $devices_n =  simplexml_load_string($sn);
        $device_ma = simplexml_load_string($ma);
        $serial_number = '';
        $mac_address = '';
        foreach ($device_ma->Row as $dma) {
            $mac_address = (string) $dma->Information;
        }
        foreach ($devices_n->Row as $dsn) {
            $serial_number = (string) $dsn->Information;
        }
        return response()->json([
            'serialnumber' => $serial_number,
            'macaddress' => $mac_address
        ]);
    }

    public function setSuperAdmin($device, $biometric_ids, $unset)
    {

        if ($tad = $this->bIO($device)) {

            // 

            function saveSettings($biometric_id, $user_data, $tad, $is_Admin, $priv)
            {
                Biometrics::where('biometric_id', $biometric_id)->update([
                    'privilege' => $priv
                ]);

                $added =  $tad->set_user_info([
                    'pin' => $user_data->biometric_id,
                    'name' => $user_data->name,
                    'privilege' => $is_Admin
                ]);


                $biometric_Data = json_decode($user_data->biometric);
                if ($added) {
                    if ($biometric_Data !== null) {
                        foreach ($biometric_Data as $row) {
                            $fingerid = $row->Finger_ID;
                            $size = $row->Size;
                            $valid = $row->Valid;
                            $template = $row->Template;
                            $tad->set_user_template([
                                'pin' => $user_data->biometric_id,
                                'finger_id' => $fingerid,
                                'size' => $size,
                                'valid' => $valid,
                                'template' => $template
                            ]);
                        }
                    }
                }
            }
            foreach ($biometric_ids as $ids) {
                $user_data = Biometrics::where('biometric_id', $ids)->get();

                if (count($user_data) >= 1) {
                    foreach ($user_data as $data) {
                        if ($unset) {

                            saveSettings($ids, $data, $tad, 0, 0);
                        } else {

                            saveSettings($ids, $data, $tad, 14, 1);
                        }
                    }
                }
            }

            //return false;
        }
    }


    public function fetchUserDataFromDBToDevice($device, $biometric_id)
    {
        if ($tad = $this->bIO($device)) {
            $user_data = Biometrics::where('biometric_id', $biometric_id)->get();
            if (count($user_data) >= 1) {
                $added =  $tad->set_user_info([
                    'pin' => $user_data[0]->biometric_id,
                    'name' => $user_data[0]->name,
                ]);
                $biometric_Data = json_decode($user_data[0]->biometric);
                if ($added) {
                    if ($biometric_Data !== null) {
                        foreach ($biometric_Data as $row) {
                            $fingerid = $row->Finger_ID;
                            $size = $row->Size;
                            $valid = $row->Valid;
                            $template = $row->Template;
                            $tad->set_user_template([
                                'pin' => $user_data[0]->biometric_id,
                                'finger_id' => $fingerid,
                                'size' => $size,
                                'valid' => $valid,
                                'template' => $template
                            ]);
                        }
                    }
                }
                return true;
            }
            return false;
        }
    }

    public function fetchUserDataFromDeviceToDB($device, $biometric_id)
    {

        if ($tad = $this->BIO($device)) {

            $user_temp = $tad->get_user_template(['pin' => $biometric_id]);
            $utemp = simplexml_load_string($user_temp);
            $BIO_User = [];
            foreach ($utemp->Row as $user_Cred) {
                /* **
            Here we are attaching the fingerprint data into our database.
            */
                $result = [
                    'Finger_ID' => (string) $user_Cred->FingerID,
                    'Size'  => (string) $user_Cred->Size,
                    'Valid' => (string) $user_Cred->Valid,
                    'Template' => (string) $user_Cred->Template,
                ];
                $BIO_User[] = $result;
            }
            $Employee_Info[] = $result;
            $validate = Biometrics::where('biometric_id', $biometric_id);
            if (count($validate->get()) >= 1) {
                if ($BIO_User[0]['Template']) {
                    $validate->update([
                        'biometric' =>  json_encode($BIO_User)
                    ]);
                }
                return true;
            } else {
                $all_user_info = $tad->get_all_user_info();
                $user_Inf = simplexml_load_string($all_user_info);
                $name = '';
                foreach ($user_Inf->Row as $row) {
                    if ($biometric_id == (string) $row->PIN2) {
                        $name = (string) $row->Name;
                    }
                }
                Biometrics::create([
                    'biometric_id' => $biometric_id,
                    'name' => $name,
                    'biometric' => $BIO_User[0]['Template'] ? json_encode($BIO_User) : "NOT_YET_REGISTERED"
                ]);
                return true;
            }
        }
        return false;
    }

    public function validateTemplate($device, $biometric_id)
    {
        try {
            if ($tad = $this->bIO($device)) {
                $user_temp = $tad->get_user_template(['pin' => $biometric_id]);
                $utemp = simplexml_load_string($user_temp);
                $BIO_User = [];
                foreach ($utemp->Row as $user_Cred) {
                    /***  
                Here we are attaching the fingerprint data into our database.
                     */
                    $result = [
                        'Finger_ID' => (string) $user_Cred->FingerID,
                        'Size'  => (string) $user_Cred->Size,
                        'Valid' => (string) $user_Cred->Valid,
                        'Template' => (string) $user_Cred->Template,
                    ];
                    $BIO_User[] = $result;
                }
                if ($BIO_User[0]['Template']) {
                    return true;
                }
            }
            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }
    /**
     * 
     * 
     *  Fetch user wth none bio data to device. for fingerprint registration */
    public function fetchDataToDeviceForNewFPRegistration($device, $biometric_id, $name)
    {
        if ($tad = $this->bIO($device)) {
            $tad->set_user_info([
                'pin' => $biometric_id,
                'name' => $name,
            ]);
            return true;
        }
        return false;
    }

    /**
     * 
     * 
     *  Fetching Selected employee
     *  */

    public function fetchDataToDevice($device, $biometric_id)
    {
        if ($tad = $this->bIO($device)) {
            $data = Biometrics::where('biometric_id', $biometric_id)
                ->Where('biometric', '!=', 'NOT_YET_REGISTERED')
                ->whereNotNull('biometric')->get();
            foreach ($data as $key => $emp) {
                $added =  $tad->set_user_info([
                    'pin' => $emp->biometric_id,
                    'name' => $emp->name,
                ]);
                $biometric_Data = json_decode($emp->biometric);
                if ($added) {
                    if ($biometric_Data !== null) {
                        foreach ($biometric_Data as $row) {
                            $fingerid = $row->Finger_ID;
                            $size = $row->Size;
                            $valid = $row->Valid;
                            $template = $row->Template;
                            $tad->set_user_template([
                                'pin' => $emp->biometric_id,
                                'finger_id' => $fingerid,
                                'size' => $size,
                                'valid' => $valid,
                                'template' => $template
                            ]);
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }

    public function saveUsersToDevices($tad, $emp, $is_Admin)
    {
        $added =  $tad->set_user_info([
            'pin' => $emp->biometric_id,
            'name' => $emp->name,
            'privilege' => $is_Admin
        ]);
        $biometric_Data = json_decode($emp->biometric);
        if ($added) {
            if ($biometric_Data !== null) {
                foreach ($biometric_Data as $row) {
                    $fingerid = $row->Finger_ID;
                    $size = $row->Size;
                    $valid = $row->Valid;
                    $template = $row->Template;
                    $tad->set_user_template([
                        'pin' => $emp->biometric_id,
                        'finger_id' => $fingerid,
                        'size' => $size,
                        'valid' => $valid,
                        'template' => $template
                    ]);
                }
            }
        }
    }
    public function fetchAllDataToDevice($device)
    {
        try {
            if ($tad = $this->bIO($device)) {
                $data = Biometrics::Where('biometric', '!=', 'NOT_YET_REGISTERED')->whereNotNull('biometric')->get();

                foreach ($data as $key => $emp) {
                    if ($emp->privilege) {
                        $this->saveUsersToDevices($tad, $emp, 14);
                    } else {
                        $this->saveUsersToDevices($tad, $emp, 0);
                    }
                }
                return true;
            }
            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function fetchSpecificDataToDevice($device, $biometricIDs)
    {
        try {
            if ($tad = $this->bIO($device)) {

                foreach ($biometricIDs as $ids) {
                    $data = Biometrics::Where('biometric', '!=', 'NOT_YET_REGISTERED')
                        ->whereNotNull('biometric')
                        ->where('biometric_id', $ids)
                        ->get();

                    foreach ($data as $key => $emp) {
                        if ($emp->privilege) {
                            $this->saveUsersToDevices($tad, $emp, 14);
                        } else {
                            $this->saveUsersToDevices($tad, $emp, 0);
                        }
                    }
                }
            }
            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Setting Device date and time ( Syncing to server )
     */
    public function setDeviceDateAndTime($device)
    {
        try {
            if ($tad = $this->bIO($device)) {
                $date = date('Y-m-d');
                $time = date('H:i:s');
                $tad->set_date(['date' => $date, 'time' => $time]);
                return true;
            }
            return false;
        } catch (\Throwable $th) {
            return $th;
        }
    }

    /* Setting Device DATE and TIME */
    public function setCustomDeviceDateAndTime($device, $time)
    {
        try {
            if ($tad = $this->bIO($device)) {

                $date = date('Y-m-d', strtotime($time));
                $time = date('H:i:s', strtotime($time));
                $tad->set_date(['date' => $date, 'time' => $time]);
                return true;
            }
            return false;
        } catch (\Throwable $th) {
            return $th;
        }
    }


    public function deleteDataFromDevice($device, $biometric_id)
    {
        try {
            if ($tad = $this->bIO($device)) {
                $tad->delete_template(['pin' =>  $biometric_id]);
                $tad->delete_user(['pin' =>  $biometric_id]);
                return true;
            }
            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function deleteAllDataFromDevice($device)
    {
        if ($tad = $this->bIO($device)) {
            $all_user_info = $tad->get_all_user_info();
            $user_Inf = simplexml_load_string($all_user_info);
            foreach ($user_Inf->Row as $row) {
                $userPin = (string) $row->PIN2;
                $tad->delete_template(['pin' =>  $userPin]);
                $tad->delete_user(['pin' =>  $userPin]);
            }
            return true;
        }
        return false;
    }

    public function deviceEnableORDisable($device, $type_of_action)
    {
        /**
         * Im not really sure how it works
         *  1 = Enable
         *  2 = Disable
         */
        try {
            if ($tad = $this->BIO($device)) {
                switch ($type_of_action) {
                    case 1:
                        $tad->enable();
                        break;
                    case 2:
                        $tad->disable();
                        break;
                }
                return true;
            }
            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function deviceShutdownORrestart($device, $type_of_action)
    {
        try {
            if ($tad = $this->BIO($device)) {
                switch ($type_of_action) {
                    case 1:
                        $tad->restart();
                        break;

                    case 2:
                        $tad->poweroff();
                        break;
                }
                return true;
            }
            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
