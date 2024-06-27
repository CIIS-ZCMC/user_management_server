<?php

namespace App\Http\Controllers\PayrollHooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeProfile;
use App\Models\DailyTimeRecord;
use Illuminate\Support\Facades\DB;
use App\Methods\Helpers;
class GenerateReportController extends Controller
{
    protected $helper;

    public function __construct()
    {
        $this->helper = new Helpers();

    }

    public function test(Request $request){
        $month_of = $request->month_of;
        $year_of = $request->year_of;
        $biometricIds = DB::table('daily_time_records')
            ->whereYear('dtr_date', $year_of)
            ->whereMonth('dtr_date', $month_of)
            ->pluck('biometric_id');
        $profiles = DB::table('employee_profiles')
            ->whereIn('biometric_id', $biometricIds)
            ->get();
            $data = [];
            foreach ($profiles as $row) {
                $Employee = EmployeeProfile::find($row->id);
                $biometric_id = $row->biometric_id;
                $dtr = DB::table('daily_time_records')
                ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                ->where(function ($query) use ($biometric_id, $month_of, $year_of) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                        ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                })
                ->orWhere(function ($query) use ($biometric_id, $month_of, $year_of) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                        ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                })
                ->get();
                $empschedule = [];
                foreach ($dtr as $val) {
                    $bioEntry = [
                        'first_entry' => $val->first_in ?? $val->second_in,
                        'date_time' => $val->first_in ?? $val->second_in
                    ];
                    $Schedule = $this->helper->CurrentSchedule($biometric_id, $bioEntry, false);
                    $DaySchedule = $Schedule['daySchedule'];
                    $empschedule[] = $DaySchedule;
                    if (count($DaySchedule) >= 1) {
                        $validate = [
                            (object)[
                                'id' => $val->id,
                                'first_in' => $val->first_in,
                                'first_out' => $val->first_out,
                                'second_in' => $val->second_in,
                                'second_out' => $val->second_out
                            ],
                        ];
                        $this->helper->saveTotalWorkingHours(
                            $validate,
                            $val,
                            $val,
                            $DaySchedule,
                            true
                        );
                    }
                }

                $presentDays = array_map(function($d) {
                    return $d->day;
                }, $dtr->toArray());

                if(count($empschedule)>=1){
                    $empschedule = array_map(function ($sc){
                    return isset($sc['scheduleDate']) && (int)date('d', strtotime($sc['scheduleDate']));
                },$empschedule);
                }

                $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);
                echo "Name :".$Employee?->personalInformation->name()?? "\n"."\n";
                for ($i=1; $i <= $days_In_Month; $i++) {
                    if(in_array($i,$presentDays) && in_array($i,$empschedule)){
                        echo $i."-Present  \n";
                    }else if (!in_array($i,$presentDays) && in_array($i,$empschedule) && date('D',strtotime($year_of.'-'.$month_of.'-'.$i)) != "Sun" && date('D',strtotime($year_of.'-'.$month_of.'-'.$i)) != "Sat"){
                        echo $i."-A  \n";
                    }

                    else {
                        echo $i."\n";
                    }
                }

                // $data[] = [
                //     'Biometric_id'=> $biometric_id,
                //     'EmployeeNo'=>$row->employee_id,
                //     'Name'=>$Employee->personalInformation->name(),
                //     'Month'=>$month_of,
                //     'Year'=>$year_of,
                //     'DTR'=>[]
                // ];
            }
        return $data;






    }
}
