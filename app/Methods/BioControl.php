<?php

namespace App\Methods;

use TADPHP\TADFactory;
use  App\Models\Biometrics;
use App\Models\Devices;
use App\Helpers\Helpers;

class BioControl
{

    /**
     * Summary of bIO
     *
     * Create and return instance [Time and Attendance Data (TAD)].
     * This is important  since this instance will be bridge in connection from this
     * Rest api to Zk Biometric Devices.
     *
     * @param mixed $device
     * @return bool|\TADPHP\TAD
     */
    public function bIO($device)
    {
        /* Validate if connected to device or not */
        try {

            $options = [
                'ip' => (string)$device['ip_address'],
                'com_key' => (int)$device['com_key'],
                'description' => 'TAD1',
                'soap_port' => (int)$device['soap_port'],
                'udp_port' => (int)$device['udp_port'],
                'encoding' => 'utf-8'
            ];
            $tad_factory = new TADFactory($options);
            $tad = $tad_factory->get_instance();
            if ($tad->is_alive()) {

                $getsnmc = json_decode($this->getSNMAC($tad)->getContent(), true);
                Devices::findorFail($device['id'])->update([
                    'serial_number' => $getsnmc['serialnumber'],
                    'mac_address' => $getsnmc['macaddress']
                ]);
                return $tad;
            }
        } catch (\Throwable $th) {

            Helpers::errorLog("BioControl", 'checkdevice', $th->getMessage());
            if (isset($device['id'])) {
                /**
                 * What is the purpose of this code ?
                 * May cause problem in a situation that where device cannot access just because it
                 * was no power and because of this code, it will result to lost access to the device.
                 */
                // Devices::findorFail($device['id'])->update([
                //     'serial_number' => null,
                //     'mac_address' => null,
                // ]);
            }
            Devices::findorFail($device['id'])->update([
                'serial_number' => null,
                'mac_address' => null,
            ]);
            return false;
        }
    }

    public function getUserInformation($attendance_Logs, $tad)
    {
        // Extract unique biometric IDs
        $biometricIds = array_reduce($attendance_Logs, function ($carry, $item) {
            $carry[] = $item['biometric_id'];
            return $carry;
        }, []);

        $uniqueBios = array_values(array_unique($biometricIds));
        $userInfoList = [];

        foreach ($uniqueBios as $PIN) {
            try {
                // Get raw XML response (bypass TAD's auto-parsing if needed)
                $response = $tad->get_user_info(['pin' => $PIN]);

                // Handle raw response if parsing fails
                $xmlString = is_object($response) ? $response->get_response_body() : $response;

                // Clean invalid XML characters
                $xmlString = mb_convert_encoding($xmlString, 'UTF-8', 'UTF-8');
                $xmlString = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $xmlString);

                // Parse XML
                $userInfo = simplexml_load_string($xmlString);
                if ($userInfo !== false) {
                    $userInfoList[] = $userInfo;
                } else {
                    \Log::error("Failed to parse XML for PIN {$PIN}: " . print_r($xmlString, true));
                }
            } catch (\Exception $e) {
                \Log::error("Error fetching user info for PIN {$PIN}: " . $e->getMessage());
            }
        }

        return $userInfoList; // Array of SimpleXMLElement objects
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

    /**
     * Summary of checkDeviceUserRecords [VERSION2]
     *
     * Associate with BioController[checkDeviceUserRecords]
     * @param mixed $device
     * @return array{Finger_ID: string, Size: string, Template: string, Valid: string[]|null}
     */
    public function checkDeviceUserRecords($device)
    {
        $tad = $this->BIO($device);

        // If tad variable has a value
        if ($tad) {
            try {
                $user_temps = $tad->get_all_user_info(['com_key' => 0]);

                $utemp = simplexml_load_string($user_temps);
                $BIO_User = [];

                foreach ($utemp->Row as $user) {
                    $result = [
                        'PIN' => (string)$user->PIN,
                        'PIN2' => (string)$user->PIN2,
                        'Name' => (string)$user->Name,
                        'Password' => (string)$user->Password,
                        'Group' => (string)$user->Group,
                        'Privilege' => (string)$user->Privilege,
                        'Card' => (string)$user->Card,
                        // Note: Fingerprint data is not available in get_all_user_info response
                    ];
                    $BIO_User[] = $result;
                }

                return [
                    "data" => $BIO_User,
                    "total_user" => count($BIO_User),
                    "message" => "Successfully retrieved all user details from the biometric device."
                ];
            } catch (\Exception $e) {
                return [
                    "data" => [],
                    "message" => "Error: " . $e->getMessage()
                ];
            }
        }

        return [
            "data" => [],
            "message" => "Failed to create TAD instance."
        ];
    }

    /**
     * Summary of findUserBiometricDetailsFromATargetDevice [VERSION2]
     *
     * Associate with BioController[checkUserBiometricDetailsFromDevice]
     * @param mixed $device
     * @param mixed $biometric_id
     * @return array{Finger_ID: string, Size: string, Template: string, Valid: string[]|null}
     */
    public function connectAndRetrieveUserDetailsFromDevice($device, $biometric_id)
    {
        $tad = $this->BIO($device);

        // If tad variable has a value
        if ($tad) {
            try {
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

                return [
                    "data" => $BIO_User,
                    "message" => "Successfully user details from the biometric device."
                ];
            } catch (\Exception $e) {
                return [
                    "data" => [],
                    "message" => "Error: " . $e->getMessage()
                ];
            }
        }

        return [
            "data" => [],
            "message" => "Failed to create TAD instance."
        ];
    }

    /**
     * Summary of populateBiometricDeviceWithEmployeesBiometricRecord
     *
     * Retrieve all employees biometric ids exclude the given ids
     * and push biometric records to device.
     *
     * @param mixed $device
     * @return void
     */
    public function populateBiometricDeviceWithEmployeesBiometricRecord($device, array $excludeUsers)
    {
        $tad = $this->BIO($device);

        try {
            if ($tad) {
                $newly_registered_biometrics = [];

                $biometrics = Biometrics::whereNotIn('name_with_biometric', $excludeUsers)
                    ->whereNot('biometric', 'NOT_YET_REGISTERED')
                    ->get()->take(10);

                foreach ($biometrics as $biometric) {
                    $privilege = $biometric->privilege ? 14 : 0;
                    $newly_registered_biometrics[] = $this->pushUserBiometricTemplateToDevice($tad, $biometric, $privilege);
                }

                return $newly_registered_biometrics;
            }
        } catch (\Throwable $th) {
            Helpers::errorLog("bioControl", 'fetchbiotoallDevices', $th->getMessage());
        }
        return [];
    }

    /**
     * Summary of pushUserBiometricTemplateToDevice [VERSION2]
     *
     * This will push user biometric details to device.
     *
     * @param mixed $tad
     * @param mixed $emp
     * @param mixed $is_Admin
     * @return void
     */
    public function pushUserBiometricTemplateToDevice($tad, $biometric, $is_Admin)
    {
        try {
            $added =  $tad->set_user_info([
                'pin' => $biometric->biometric_id,
                'name' => $biometric->name_with_biometric,
                'privilege' => $is_Admin
            ]);

            $biometric_template = $biometric->biometric;
            $biometric_Data = json_decode($biometric_template);

            if ($added) {
                if ($biometric_Data !== null) {
                    foreach ($biometric_Data as $row) {
                        $fingerid = $row->Finger_ID;
                        $size = $row->Size;
                        $valid = $row->Valid;
                        $template = $row->Template;
                        $tad->set_user_template([
                            'pin' => $biometric->biometric_id,
                            'finger_id' => $fingerid,
                            'size' => $size,
                            'valid' => $valid,
                            'template' => $template
                        ]);
                    }
                    Helpers::infoLog("BioControl", 'saveUSERSTODEVICE', 'PASSED');
                    return $biometric;
                }
            }
        } catch (\Throwable $th) {
            Helpers::errorLog("BioControl", 'saveUSERSTODEVICE', $th->getMessage());
            return [];
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

            // $Employee_Info[] = $result;
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
        try {

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
                    Helpers::infoLog("BioControl", 'saveUSERSTODEVICE', 'PASSED');
                }
            }
        } catch (\Throwable $th) {
            Helpers::errorLog("BioControl", 'saveUSERSTODEVICE', $th->getMessage());
        }
    }

    public function fetchAllDataToDevice($device)
    {
        try {
            if ($tad = $this->bIO($device)) {
                $biometrics = Biometrics::where('privilege', 1)->get();

                foreach ($biometrics as $biometric) {
                    $this->saveUsersToDevices($tad, $biometric, $biometric->privilege ? 14 : 0);
                }

                // Biometrics::where('biometric', '!=', 'NOT_YET_REGISTERED')
                //     ->whereNotNull('biometric')
                //     ->chunk(20, function ($users) use ($tad) {
                //         foreach ($users as $emp) {
                //             $privilege = $emp->privilege ? 14 : 0;
                //             $this->saveUsersToDevices($tad, $emp, $privilege);
                //         }
                //         // Optional: Add a small delay to avoid overloading the device
                //         sleep(3);
                //     });
            }
        } catch (\Throwable $th) {
            Helpers::errorLog("bioControl", 'fetchbiotoallDevices', $th->getMessage());
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

    /**
     * Summary of deleteDataFromDevice
     *
     * This will delete user bioemtric record from a device
     *
     * @param mixed $device
     * @param mixed $biometric_id
     * @return bool
     */
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
