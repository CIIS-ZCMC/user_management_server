<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Methods\BioControl;
use App\Models\Devices;
use App\Models\Biometrics;
class PullRegistrationDataBulkCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pull-registration-data';

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
    protected $description = 'Bulk pulling of data from biometric devices, that is being used in registration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
       $this->info("Initializing ...");
       $ip_address = [
         // "192.168.5.158",
         // "192.168.5.157",
          "192.168.5.171"
       ];
      $devices = Devices::where("is_active",1)
      ->whereIn("ip_address",$ip_address)
      ->get();

      $summary = [];

      foreach ($devices as $device) {
        $this->warn("Connecting to  $device->ip_address");
        if ($tad = $this->device->bIO($device)) {
            $this->info("Connection successful -------- $device->ip_address");
           
            $biometrics = Biometrics::all();
          // $biometrics = Biometrics::where("biometric_id",493)->get();
           foreach ($biometrics as $emp) {
            $user_temp = $tad->get_user_template(['pin' => $emp->biometric_id]);
            $utemp = simplexml_load_string($user_temp);
            
            
            $info = $utemp !== false && isset($utemp->Row->Information)
            ? trim((string) $utemp->Row->Information)
            : null;

            if (!isset($summary[$device->ip_address])) {
              $summary[$device->ip_address] = [
                  'processed_count' => 0,
                  'unprocessed_count' => 0, 
              ];
          }
        
            if ($info !== "No data!") {
                $BIO_User = [];
                foreach ($utemp->Row as $user_Cred) {
                    $result = [
                        'Finger_ID' => (string) $user_Cred->FingerID,
                        'Size'  => (string) $user_Cred->Size,
                        'Valid' => (string) $user_Cred->Valid,
                        'Template' => (string) $user_Cred->Template,
                    ];
                    $BIO_User[] = $result;
                }
                   if( $emp->update([
                    'biometric' =>  json_encode($BIO_User)
                      ])){
                        $this->line("$device->ip_address Processed -> {$emp->biometric_id} {$emp->name}  ✔");
                        $summary[$device->ip_address]['processed_count']++;
                      }
            }else {
                $this->error("$device->ip_address Unprocessed -> {$emp->biometric_id} {$emp->name} ");
                $summary[$device->ip_address]['unprocessed_count']++;
              }
           }
        }else {
            $this->error("Connection failed --- $device->ip_address");
        }

        $this->info("End of process for --- $device->ip_address ");
      }

      $this->info("======= SUMMARY =======");
      foreach ($summary as $ip => $data) {
          $this->line("$ip -> Processed: {$data['processed_count']}, Unprocessed: {$data['unprocessed_count']}");
      }
      
    }
}
