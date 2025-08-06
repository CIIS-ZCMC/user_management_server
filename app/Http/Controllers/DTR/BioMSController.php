<?php

namespace App\Http\Controllers\DTR;

use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Models\Devices;
use App\Methods\BioControl;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use App\Models\Biometrics;

class BioMSController extends Controller
{
    protected $device;

    private $CONTROLLER_NAME = "BioMSController";

    public function __construct()
    {
        $this->device = new BioControl();
    }
    public function index()
    {

        try {
            $bios = Devices::all();
            $data = [];
            $status = "Offline";
            foreach ($bios as $row) {
                
                if($row->is_active){
                      if (!$this->device->BIO($row)) {
                    $status = "Offline";
                } else {
                    $status = "Online";
                }
                }else {
                    $status = "Offline";
                }
              

                $item = [
                    "id" => $row->id,
                    "device_name" => $row->device_name,
                    "ip_address" => $row->ip_address,
                    "com_key" => $row->com_key,
                    "soap_port" => $row->soap_port,
                    "udp_port" => $row->udp_port,
                    "serial_number" => $row->serial_number,
                    "mac_address" => $row->mac_address,
                    "is_registration" => $row->is_registration,
                    "device_status" => $status,
                    "is_active"=>$row->is_active,
                    "for_attendance"=>$row->for_attendance,
                    "receiver_by_default"=>$row->receiver_by_default,
                    "created_at" => $row->created_at,
                    "updated_at" => $row->updated_at
                ];

                // If is_registration is 1, add the item to the beginning of the array
                if ($row->is_registration == 1) {
                    array_unshift($data, $item);
                } else {
                    $data[] = $item;
                }
            }
            return response()->json([
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateDeviceStatus(Request $request)
    {
        try {
            $device = Devices::find($request->id);
            $device->update([
                $request->field => $request->value
            ]);
            return response()->json(['message' => 'Device status updated successfully']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updateDeviceStatus', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

  public function operatingDevice()
    {
        try {
            $data = Devices::where('is_registration', 0)->where("for_attendance",0)->get();

            return response()->json([
                'data' => $data ?? []
            ]);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'operatingDevice', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function registrationDevice()
    {
        try {
            $data = Devices::where('is_registration', 1)->where("for_attendance",0)->get();

            return response()->json([
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'registrationDevice', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function addDevice(Request $request)
    {

        try {
            $device_name = $request->device_name;
            $ip_address = $request->ip_address;
            $com_key = $request->com_key;
            $soap_port = $request->soap_port;
            $udp_port = $request->udp_port;
            $is_registration = $request->is_registration;

            if (!$com_key) {
                $com_key = 0;
            }
            if (!$soap_port) {
                $soap_port = 80;
            }
            if (!$udp_port) {
                $udp_port = 4370;
            }
            if (!$is_registration) {
                $is_registration = 0;
            }

            $validation = Devices::where('ip_address', $ip_address)->get();
            if (count($validation) == 0) {
                /* **
                * here we only allow 1 registration device

                */
                if ($is_registration) {
                    $check_if_registration_exist = Devices::where('is_registration', 1)->get();
                    if (count($check_if_registration_exist) >= 1) {
                        return response()->json(['message' => 'Registration Device Already Exist']);
                    }
                }
                Devices::create([
                    'device_name' => $device_name,
                    'ip_address' => $ip_address,
                    'com_key'    => $com_key,
                    'soap_port'  => $soap_port,
                    'udp_port'   => $udp_port,
                    'is_registration' => $is_registration
                ]);
                return response()->json(['message' => 'Device added Successfully!']);
            }
            return response()->json(['message' => 'Device Already Exist']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'addDevice', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function testDeviceConnection(Request $request)
    {
        try {
            $device_id = $request->device_id;
            $device = Devices::find($device_id);

            if ($this->device->bIO($device)) {
                return response()->json(['message' => 'Connection Successful']);
            }
            return response()->json(['message' => 'Connection Failed']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'testDeviceConnection', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateDevice(Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);



            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }



            $device_id = $request->device_id;
            $device_name = $request->device_name;
            $ip_address = $request->ip_address;
            $com_key = $request->com_key;
            $soap_port = $request->soap_port;
            $udp_port = $request->udp_port;
            $is_registration = $request->is_registration;
            if (!$com_key) {
                $com_key = 0;
            }
            if (!$soap_port) {
                $soap_port = 80;
            }
            if (!$udp_port) {
                $udp_port = 4370;
            }

            if ($is_registration) {
                $validate =  Devices::where('is_registration', 1)->get();

                if (count($validate) >= 1) {
                    if ($validate[0]->id == $device_id) {

                        Devices::findorFail($device_id)
                            ->update([
                                'device_name' => $device_name,
                                'ip_address' => $ip_address,
                                'com_key' => $com_key,
                                'soap_port' => $soap_port,
                                'udp_port' => $udp_port,
                            ]);

                        return response()->json(['message' => 'Device Updated Successfully!']);
                    }
                }
            }

            Devices::findorFail($device_id)
                ->update([
                    'device_name' => $device_name,
                    'ip_address' => $ip_address,
                    'com_key' => $com_key,
                    'soap_port' => $soap_port,
                    'udp_port' => $udp_port,
                    'is_registration' => $is_registration
                ]);
            return response()->json(['message' => 'Device Updated Successfully!']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updateDevice', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function deleteDevice(Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->pin);



            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }
            $device_id = $request->device_id;
            Devices::findorFail($device_id)->delete();
            return response()->json(['message' => 'Device Deleted Successfully!']);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'deleteDevice', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function fetchBiometrics(Request $request)
    {
        try {
            $data = Biometrics::all();
            return $data;
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'fetchBiometrics', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
