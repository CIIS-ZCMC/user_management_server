@if (isset($dtr_dayoff))
    <td class="time " style="font-size:10px;font-weight:bold" id="entry{{ $i }}1">
        DAY OFF

    </td>

    <td class="time " style="font-weight:bold" id="entry{{ $i }}2">
    
        @php
            $yesterDate = date('Y-m-d', strtotime($curDate . ' -1 day'));
            $yesdtr = array_values(
                array_filter($dtrRecords->toArray(), function ($row) use ($yesterDate) {
                    return $row->dtr_date == $yesterDate;
                }),
            );

            if (count($yesdtr) >= 1) {
                if (
                    (date('H', strtotime($yesdtr[0]->first_in)) >= 18 &&
                        date('H', strtotime($yesdtr[0]->first_in) <= 23)) ||
                    date('H') == 0
                ) {
                    if (
                        $curDate == date('Y-m-d', strtotime($yesdtr[0]->first_out)) &&
                        date('a', strtotime($yesdtr[0]->first_out)) == 'am'
                    ) {
                        echo date('h:i a', strtotime($yesdtr[0]->first_out)) ;
                    }
                }
            }

            if ($i == 1) {
                //Check previous month for timeout;
                $previousMonthNumber = date('m', strtotime($curDate)) - 1;
                $previousYear =
                    $previousMonthNumber == 12 ? date('Y', strtotime($curDate)) - 1 : date('Y', strtotime($curDate));
                $lastdayoftheMonth = cal_days_in_month(CAL_GREGORIAN, $previousMonthNumber, $previousYear);
                $lastdateofTheMonth = date(
                    'Y-m-d',
                    strtotime($previousYear . '-' . $previousMonthNumber . '-' . $lastdayoftheMonth),
                );

                $dtr = DB::table('daily_time_records')
                    ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                    ->where(function ($query) use (
                        $biometric_id,
                        $previousMonthNumber,
                        $previousYear,
                        $lastdateofTheMonth,
                    ) {
                        $query
                            ->where('dtr_date', $lastdateofTheMonth)
                            ->where('biometric_id', $biometric_id)
                            ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $previousMonthNumber)
                            ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $previousYear);
                    })
                    ->first();

                if ($dtr) {
                    //Compare the last month sched.
                //Check schedule. 
                $lmD = date('Y-m-d', strtotime($dtr->first_out));

                    $sched = $Schedule->filter(function ($res) use ($lmD) {
                        return $res->schedule == $lmD;
                    });
                    //if not second in and  second out
                        //- morning - am only..

                        $firstSched = $sched->first();

                if ($firstSched && !$firstSched->second_in && !$firstSched->second_out) {
                    if(date('a', strtotime($dtr->first_out)) == "am"){
                        echo date('Y-m-d h:i a', strtotime($dtr->first_out)) ;
                    }         
                }                 
                }
            }
        @endphp


    </td>
    <td class="time " id="entry{{ $i }}3">

    </td>
    <td class="time " id="entry{{ $i }}4">

    </td>
@endif
