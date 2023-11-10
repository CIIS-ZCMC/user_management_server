<?php

namespace App\Methods;

use TADPHP\TADFactory;
use  App\Models\biometrics;
use App\Models\devices;

class Bio_contr
{

    public function BIO($device)
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
                $getsnmc = json_decode($this->Get_SN_MAC($tad)->getContent(), true);
                devices::findorFail($device['id'])->update([
                    'serial_number' => $getsnmc['serialnumber'],
                    'mac_address' => $getsnmc['macaddress']
                ]);
                return $tad;
            }
        } catch (\Throwable $th) {
            devices::findorFail($device['id'])->update([
                'serial_number' => null,
                'mac_address' => null,
            ]);
            return false;
        }
    }

    public function Get_SN_MAC($tad)
    {
        $sn = $tad->get_serial_number();
        $ma = $tad->get_mac_address();
        $devicesn =  simplexml_load_string($sn);
        $devicema = simplexml_load_string($ma);
        $serial_number = '';
        $macaddress = '';
        foreach ($devicema->Row as $dma) {
            $macaddress = (string) $dma->Information;
        }
        foreach ($devicesn->Row as $dsn) {
            $serial_number = (string) $dsn->Information;
        }
        return response()->json([
            'serialnumber' => $serial_number,
            'macaddress' => $macaddress
        ]);
    }

    public function Set_SuperAdmin($device, $biometric_id, $unset)
    {
        if ($tad = $this->BIO($device)) {
            $userdata = biometrics::where('biometric_id', $biometric_id)->get();
            function SaveSettings($biometric_id, $userdata, $tad, $isAdmin, $priv)
            {
                biometrics::where('biometric_id', $biometric_id)->update([
                    'privilege' => $priv
                ]);
                $added =  $tad->set_user_info([
                    'pin' => $userdata[0]->biometric_id,
                    'name' => $userdata[0]->name,
                    'privilege' => $isAdmin
                ]);
                $biometricData = json_decode($userdata[0]->biometric);
                if ($added) {
                    if ($biometricData !== null) {
                        foreach ($biometricData as $row) {
                            $fingerid = $row->Finger_ID;
                            $size = $row->Size;
                            $valid = $row->Valid;
                            $template = $row->Template;
                            $tad->set_user_template([
                                'pin' => $userdata[0]->biometric_id,
                                'finger_id' => $fingerid,
                                'size' => $size,
                                'valid' => $valid,
                                'template' => $template
                            ]);
                        }
                    }
                }
            }
            if (count($userdata) >= 1) {
                if ($unset) {
                    SaveSettings($biometric_id, $userdata, $tad, 0, 0);
                } else {
                    SaveSettings($biometric_id, $userdata, $tad, 14, 1);
                }
                return true;
            }
            return false;
        }
    }


    public function FetchUser_datafromDB_toDevice($device, $biometric_id)
    {
        if ($tad = $this->BIO($device)) {
            $userdata = biometrics::where('biometric_id', $biometric_id)->get();
            if (count($userdata) >= 1) {
                $added =  $tad->set_user_info([
                    'pin' => $userdata[0]->biometric_id,
                    'name' => $userdata[0]->name,
                ]);
                $biometricData = json_decode($userdata[0]->biometric);
                if ($added) {
                    if ($biometricData !== null) {
                        foreach ($biometricData as $row) {
                            $fingerid = $row->Finger_ID;
                            $size = $row->Size;
                            $valid = $row->Valid;
                            $template = $row->Template;
                            $tad->set_user_template([
                                'pin' => $userdata[0]->biometric_id,
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

    public function FetchUser_datafromdevice_toDB($device, $biometric_id)
    {
        if ($tad = $this->BIO($device)) {
            $usertemp = $tad->get_user_template(['pin' => $biometric_id]);
            $utemp = simplexml_load_string($usertemp);
            $BIOUser = [];
            foreach ($utemp->Row as $userCred) {
                /* **
            Here we are attaching the fingerprint data into our database.
            */
                $result = [
                    'Finger_ID' => (string) $userCred->FingerID,
                    'Size'  => (string) $userCred->Size,
                    'Valid' => (string) $userCred->Valid,
                    'Template' => (string) $userCred->Template,
                ];
                $BIOUser[] = $result;
            }
            $EmployeeInfo[] = $result;
            $validate = biometrics::where('biometric_id', $biometric_id);
            if (count($validate->get()) >= 1) {
                if ($BIOUser[0]['Template']) {
                    $validate->update([
                        'biometric' =>  json_encode($BIOUser)
                    ]);
                }
                return true;
            } else {
                $all_user_info = $tad->get_all_user_info();
                $userInf = simplexml_load_string($all_user_info);
                $name = '';
                foreach ($userInf->Row as $row) {
                    if ($biometric_id == (string) $row->PIN2) {
                        $name = (string) $row->Name;
                    }
                }
                biometrics::create([
                    'biometric_id' => $biometric_id,
                    'name' => $name,
                    'biometric' => $BIOUser[0]['Template'] ? json_encode($BIOUser) : "NOT_YET_REGISTERED"
                ]);
                return true;
            }
        }
        return false;
    }

    public function ValidateTemplate($device, $biometric_id)
    {
        try {
            if ($tad = $this->BIO($device)) {
                $usertemp = $tad->get_user_template(['pin' => $biometric_id]);
                $utemp = simplexml_load_string($usertemp);
                $BIOUser = [];
                foreach ($utemp->Row as $userCred) {
                    /***  
                Here we are attaching the fingerprint data into our database.
                     */
                    $result = [
                        'Finger_ID' => (string) $userCred->FingerID,
                        'Size'  => (string) $userCred->Size,
                        'Valid' => (string) $userCred->Valid,
                        'Template' => (string) $userCred->Template,
                    ];
                    $BIOUser[] = $result;
                }
                if ($BIOUser[0]['Template']) {
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
    public function FetchdatatoDevice_for_New_FP_Registration($device, $biometric_id, $name)
    {
        if ($tad = $this->BIO($device)) {
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

    public function Fetch_data_to_device($device, $biometric_id)
    {
        if ($tad = $this->BIO($device)) {
            $data = biometrics::where('biometric_id', $biometric_id)
                ->Where('biometric', '!=', 'NOT_YET_REGISTERED')
                ->whereNotNull('biometric')->get();
            foreach ($data as $key => $emp) {
                $added =  $tad->set_user_info([
                    'pin' => $emp->biometric_id,
                    'name' => $emp->name,
                ]);
                $biometricData = json_decode($emp->biometric);
                if ($added) {
                    if ($biometricData !== null) {
                        foreach ($biometricData as $row) {
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

    public function Fetchall_data_to_device($device)
    {
        try {
            if ($tad = $this->BIO($device)) {
                $data = biometrics::Where('biometric', '!=', 'NOT_YET_REGISTERED')->whereNotNull('biometric')->get();
                function SaveSettings($tad, $emp, $isAdmin)
                {
                    $added =  $tad->set_user_info([
                        'pin' => $emp->biometric_id,
                        'name' => $emp->name,
                        'privilege' => $isAdmin
                    ]);
                    $biometricData = json_decode($emp->biometric);
                    if ($added) {
                        if ($biometricData !== null) {
                            foreach ($biometricData as $row) {
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
                foreach ($data as $key => $emp) {
                    if ($emp->privilege) {
                        SaveSettings($tad, $emp, 14);
                    } else {
                        SaveSettings($tad, $emp, 0);
                    }
                }
                return true;
            }
            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Setting Device date and time ( Syncing to server )
     */
    public function Set_device_dateandtime($device)
    {
        try {
            if ($tad = $this->BIO($device)) {
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
    public function Set_CustomDevice_dateandtime($device, $time)
    {
        try {
            if ($tad = $this->BIO($device)) {

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


    public function Delete_datafromdevice($device, $biometric_id)
    {
        try {
            if ($tad = $this->BIO($device)) {
                $tad->delete_template(['pin' =>  $biometric_id]);
                $tad->delete_user(['pin' =>  $biometric_id]);
                return true;
            }
            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function DeleteAll_datafromdevice($device)
    {
        if ($tad = $this->BIO($device)) {
            $all_user_info = $tad->get_all_user_info();
            $userInf = simplexml_load_string($all_user_info);
            foreach ($userInf->Row as $row) {
                $userPin = (string) $row->PIN2;
                $tad->delete_template(['pin' =>  $userPin]);
                $tad->delete_user(['pin' =>  $userPin]);
            }
            return true;
        }
        return false;
    }

    public function Device_Enable_OR_Disable($device, $TypeofAction)
    {
        /**
         * Im not really sure how it works
         *  1 = Enable
         *  2 = Disable
         */
        try {
            if ($tad = $this->BIO($device)) {
                switch ($TypeofAction) {
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

    public function Device_Shutdown_OR_restart($device, $TypeofAction)
    {
        try {
            if ($tad = $this->BIO($device)) {
                switch ($TypeofAction) {
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
