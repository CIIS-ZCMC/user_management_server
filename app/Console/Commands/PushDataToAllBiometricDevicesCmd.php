<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Methods\BioControl;
use App\Models\Devices;
use App\Models\Biometrics;

class PushDataToAllBiometricDevicesCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:push-data-to-all-devices';

    /**
     * The console command description.
     *
     * @var string
     */

     protected $device;

     public function __construct() {
        parent::__construct();
        $this->device = new BioControl();
     }
    protected $description = 'Dynamically pushing data on active devices, selected or set.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Initializing process ...");

        $devices = Devices::where("is_active",1)
        ->where("ip_address","192.168.5.171")
        ->get();

        foreach ($devices as $device) {
         $this->warn("Connecting to  $device->ip_address");
         if ($tad = $this->device->bIO($device)) {
            $this->info("Connection successful -------- $device->ip_address  ");
            //✘✔
            // Logic
            /**
             * Pushing Logic .. 
             */

             $biometrics = Biometrics::whereNot("biometric","NOT_YET_REGISTERED")->get();
             //$biometrics = Biometrics::where("biometric_id",493)->get();
             foreach ($biometrics as $emp) {

                $user_temp = $tad->get_user_template(['pin' => $emp->biometric_id]);
                $utemp = simplexml_load_string($user_temp);

                $info = $utemp !== false && isset($utemp->Row->Information)
                ? trim((string) $utemp->Row->Information)
                : null;
                $BIO_User = [];
                if ($info !== "No data!") {
                    foreach ($utemp->Row as $user_Cred) {
                        $result = [
                            'Finger_ID' => (string) $user_Cred->FingerID,
                            'Size'  => (string) $user_Cred->Size,
                            'Valid' => (string) $user_Cred->Valid,
                            'Template' => (string) $user_Cred->Template,
                        ];
                        $BIO_User[] = $result;
                    }
                }
               
                $added =  $tad->set_user_info([
                    'pin' => $emp->biometric_id,
                    'name' => $emp->name,
                    'privilege' => $emp->privilege ? 14 : 0
                ]);
                $biometric_Data = json_decode($emp->biometric, true); 
                if (!empty($BIO_User)) {
                    $merged = array_merge($biometric_Data ?? [], $BIO_User);
                    $biometric_Data = collect(array_values(array_reduce($merged, function ($carry, $item) {
                        $carry[$item['Finger_ID']] = $item; 
                        return $carry;
                    }, [])));
                }
                $biometric_Data = json_decode(json_encode($biometric_Data));
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
                        $this->line("$device->ip_address Saved -> {$emp->biometric_id} {$emp->name}  ✔");
                    }
                }else {
                    $this->error("$device->ip_address Failed -> {$emp->biometric_id} {$emp->name} ");
                }
             }
         }else {
            $this->error("Connection failed --- $device->ip_address");
         }

         $this->info("End of process for --- $device->ip_address ");
        }

  
    }
}
