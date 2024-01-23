<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use App\Models\Devices;
use App\Methods\BioControl;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;

class BioMSController extends Controller
{
    protected $device;

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

                if (!$this->device->BIO($row)) {
                    $status = "Offline";
                } else {
                    $status = "Online";
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
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function operatingDevice()
    {

        try {
            $data = Devices::where('is_registration', 0)->get();

            return response()->json([
                'data' => $data ?? []
            ]);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function registrationDevice()
    {

        try {
            $data = Devices::where('is_registration', 1)->get();

            return response()->json([
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
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
            return response()->json(['message' =>  $th->getMessage()]);
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
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function updateDevice(Request $request)
    {
        try {
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
            return response()->json(['message' => $th->getMessage()]);
        }
    }


    public function deleteDevice(Request $request)
    {
        try {
            $user = $request->user;
            $password_decrypted = Crypt::decryptString($user['password_encrypted']);
            $password = strip_tags($request->password);
            if (!Hash::check($password . env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $device_id = $request->device_id;
            Devices::findorFail($device_id)->delete();
            return response()->json(['message' => 'Device Deleted Successfully!']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }
}
