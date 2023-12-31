<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use App\Models\devices;
use App\Methods\Bio_contr;
use App\Http\Controllers\Controller;

class BioMSController extends Controller
{
    protected $Device;

    public function __construct()
    {
        $this->Device = new Bio_contr();
    }
    public function index()
    {

        try {
            $bios = devices::all();
            $data = [];
            $status = "Offline";
            foreach ($bios as $row) {

                if (!$this->Device->BIO($row)) {
                    $status = "Offline";
                } else {
                    $status = "Online";
                }

                $data[] = [
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
            }
            return response()->json([
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function operating_device()
    {

        try {
            $data = devices::where('is_registration', 0)->get();

            return response()->json([
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function registration_device()
    {

        try {
            $data = devices::where('is_registration', 1)->get();

            return response()->json([
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }


    public function add_device(Request $request)
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

            $validation = devices::where('ip_address', $ip_address)->get();
            if (count($validation) == 0) {
                /* **
                * here we only allow 1 registration device
                
                */
                if ($is_registration) {
                    $checkifregistrationexist = devices::where('is_registration', 1)->get();
                    if (count($checkifregistrationexist) >= 1) {
                        return response()->json(['message' => 'Registration Device Already Exist']);
                    }
                }
                devices::create([
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

    public function test_device_connection(Request $request)
    {
        try {
            $device_id = $request->device_id;
            $device = devices::find($device_id);

            if ($this->Device->BIO($device)) {
                return response()->json(['message' => 'Connection Successful']);
            }
            return response()->json(['message' => 'Connection Failed']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function Update_device(Request $request)
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
                $validate =  devices::where('is_registration', 1)->get();

                if (count($validate) >= 1) {
                    if ($validate[0]->id == $device_id) {

                        devices::findorFail($device_id)
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

            devices::findorFail($device_id)
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


    public function Delete_device(Request $request)
    {
        try {
            $device_id = $request->device_id;
            devices::findorFail($device_id)->delete();
            return response()->json(['message' => 'Device Deleted Successfully!']);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }
}
