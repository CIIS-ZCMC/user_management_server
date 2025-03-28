@if ($isHoliday && count($dtr) == 0)
    {{-- HOLIDAY --}}
    <td class="time" style="font-size:10px;letter-spacing:5px;color:rgb(214, 164, 0);" id="entry{{ $i }}1">
        <span class="" style="letter-spacing:  5px">{{ $holidayMessage }}</span>
        <script>
            $(document).ready(function() {
                $("#entry{{ $i }}1").addClass("Holiday");
            });
        </script>
    </td>
    <td class="time" id="entry{{ $i }}2"></td>
    <td class="time" id="entry{{ $i }}3"></td>
    <td class="time" id="entry{{ $i }}4"></td>
@else
    @if ($leave_Count || $ot_Count || $ob_Count || $cto_Count)

        @if ($cto_Count)
            @if (isset($firstin_))
                @include('dtr.ctoFormat', ['dtr_CTO1' => true])
            @else
                @include('dtr.ctoFormat', ['dtr_CTO2' => true])
            @endif
        @else
            <td class="time" style="font-size:10px;font-weight:bold" colspan="4" id="entry{{ $i }}1">
                @if ($leave_Count)
                    <span class="" style="font-weight: normal">{{ $leavemessage }}</span>
                @elseif ($ot_Count)
                    <span class="">{{ $officialTime }}</span>
                @elseif ($ob_Count)
                    <span class="">{{ $officialBusinessMessage }}</span>
                @endif
            </td>
        @endif
    @else
        @if (count($empSched) == 0)
            @include('dtr.dayoffFormat', ['dtr_dayoff' => true]) 
        @else
            @if (count($dtr) == 0 && count($empSched) && $empSched[0]->schedule < date('Y-m-d'))
                @include('dtr.absentFormat', ['dtr_absent' => true])
            @else
                <td class="time" id="entry{{ $i }}1">

                    <!--FIRST IN -->
                    @if (count($dtr) && $dtr[0]->first_in)
                        @php
                            if (
                                $curDate == date('Y-m-d', strtotime($dtr[0]->first_in)) &&
                                date('a', strtotime($dtr[0]->first_in)) == 'am'
                            ) {
                                echo date('h:i a', strtotime($dtr[0]->first_in));
                            }
                        @endphp
                    @else
                        @php
                            $firstin = true;
                        @endphp
                        <span style="font-size:8px;color:rgb(177, 166, 166)">NO ENTRY</span>
                    @endif
                </td>
                <td class="time" id="entry{{ $i }}2">
                
                    @php
                        $yesterDate = date('Y-m-d', strtotime($curDate . ' -1 day'));
                        $yesdtr = array_values(
                            array_filter($dtrRecords->toArray(), function ($row) use ($yesterDate) {
                                return $row->dtr_date == $yesterDate;
                            }),
                        );

                        $tomorrowDate = date('Y-m-d', strtotime($curDate . ' +1 day'));
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
                                    echo date('h:i a', strtotime($yesdtr[0]->first_out));
                                }
                            }
                        }
                    @endphp
                    @if (count($dtr) && $dtr[0]->first_out)
                        @php
                            if (
                                $curDate == date('Y-m-d', strtotime($dtr[0]->first_out)) &&
                                date('a', strtotime($dtr[0]->first_out)) == 'am'
                            ) {
                                echo date('h:i a', strtotime($dtr[0]->first_out));
                            }
                        @endphp
                    @else
                        <span style="font-size:8px;color:rgb(177, 166, 166)">NO ENTRY</span>
                    @endif


                </td>
                <td class="time" id="entry{{ $i }}3">
                    @if (count($dtr) && $dtr[0]->first_in)
                        @php
                            if (
                                $curDate == date('Y-m-d', strtotime($dtr[0]->first_in)) &&
                                date('a', strtotime($dtr[0]->first_in)) == 'pm'
                            ) {
                                echo date('h:i a', strtotime($dtr[0]->first_in));
                            }
                        @endphp
                    @else
                        @php
                            $secondin = true;
                            $previousTimestamp = date('h:i a', strtotime($dtr[0]->first_in));
                        @endphp

                        @if($dtr[0]->second_in &&  date('a', strtotime($dtr[0]->second_in)) == 'pm')
                            {{ date('h:i a', strtotime($dtr[0]->second_in)) }}

                        @else 
                        <span style="font-size:8px;color:rgb(177, 166, 166)">NO ENTRY</span>
                        @endif
                      
                    @endif

                </td>
                <td class="time" id="entry{{ $i }}4">
                    @if (count($dtr) && $dtr[0]->first_out)
                        @php
                            if (
                                $curDate == date('Y-m-d', strtotime($dtr[0]->first_out)) &&
                                date('a', strtotime($dtr[0]->first_out)) == 'pm'
                            ) {
                                echo date('h:i a', strtotime($dtr[0]->first_out));
                            }
                        @endphp
                    @else
                        @php
                            $secondout = true;
                        @endphp


                    @if($dtr[0]->second_out &&  date('a', strtotime($dtr[0]->second_out)) == 'pm')
                            {{ date('h:i a', strtotime($dtr[0]->second_out)) }}

                        @else 
                        <span style="font-size:8px;color:rgb(177, 166, 166)">NO ENTRY</span>
                        @endif
                       
                    @endif

                </td>
            @endif
        @endif
    @endif
@endif

<td style="width: 40px !important; padding:10px;border-right :1px solid rgb(196, 197, 201);border-left :1px solid rgb(196, 197, 201)"
    class="time">
    @include('dtr.DtrSeparator', ['entry' => 'undertime'])
</td>

<td style="background-color: whitesmoke;width: 80px !important;border-right: 1px solid rgb(184, 184, 184)"
    id="wsched{{ $i }}">
    @if (count($empSched) >= 1)
        <span style="font-size: 11px;color:gray">
            @if (!$empSched[0]->second_in && !$empSched[0]->second_out)
                {{ date('G', strtotime($empSched[0]->first_in)) }} -
                {{ date('G', strtotime($empSched[0]->first_out)) }}
            @else
                {{ date('G', strtotime($empSched[0]->first_in)) }} -
                {{ date('G', strtotime($empSched[0]->first_out)) }} |
                {{ date('G', strtotime($empSched[0]->second_in)) }} -
                {{ date('G', strtotime($empSched[0]->second_out)) }}
            @endif
        </span>
        <script>
            $("#wsched{{ $i }}").addClass("wsched");
        </script>
    @endif
</td>
<td style="background-color: whitesmoke;width: 80px !important;border-right: 1px solid rgb(184, 184, 184)">
    <span style="font-size:13px;color:gray;">
        @php
            try {
                if (count($dtr)) {
                    $firstIn = $dtr[0]->first_in ?? null;
                    $firstOut = $dtr[0]->first_out ?? null;

                    $currDate = date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                    if ($firstIn && $firstOut) {
                        //daySchedule

                        $helper = new \App\Methods\Helpers();

                        $bioEntry = [
                            'first_entry' => $firstIn,
                            'date_time' => $firstIn,
                        ];
                        //Get matching Schedule.
                        $Schedule = $helper->CurrentSchedule($biometric_id, $bioEntry, false);

                        $controller = new \App\Http\Controllers\PayrollHooks\GenerateReportController();
                        $nightDifferentialHours = $controller->getNightDifferentialHours(
                            $firstIn,
                            $firstOut,
                            $biometric_id,
                            [],
                            $Schedule['daySchedule'],
                        );

                        echo $nightDifferentialHours['total_hours'];
                    }
                } //code...
            } catch (\Throwable $th) {
                //throw $th;
            }
        @endphp
    </span>
</td>
<td style="background-color: whitesmoke;width: 200px !important;">
    <span style="font-size:13px;color:gray">
        @php
            if (count($empSched) == 0) {
                echo '[ No Schedule ]';
            }

            if (!count($empSched) && (isset($dtr) && !count($dtr))) {
                echo '[ Without Leave ]';
            }

            if ($leave_Count) {
                $leave = array_values(
                    array_filter($leaveapp, function ($res) use ($year, $month, $i) {
                        return array_filter($res['dates_covered'], function ($row) use ($year, $month, $i) {
                            $dateToCompare = date('Y-m-d', strtotime($row));
                            $dateToMatch = date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                            return $dateToCompare === $dateToMatch;
                        });
                    }),
                );

                echo "{$leave[0]['city']}  {$leave[0]['country']} ";
            }

            if (count($empSched) >= 1) {
                if ($ot_Count) {
                    $officialtime = array_values(
                        array_filter($otApp, function ($res) use ($year, $month, $i) {
                            return array_filter($res['dates_covered'], function ($row) use ($year, $month, $i) {
                                $dateToCompare = date('Y-m-d', strtotime($row));
                                $dateToMatch = date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                                return $dateToCompare === $dateToMatch;
                            });
                        }),
                    );
                    echo "{$officialtime[0]['purpose']} ";
                }

                if ($ob_Count) {
                    $officialbusiness = array_values(
                        array_filter($obApp, function ($res) use ($year, $month, $i) {
                            return array_filter($res['dates_covered'], function ($row) use ($year, $month, $i) {
                                $dateToCompare = date('Y-m-d', strtotime($row));
                                $dateToMatch = date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                                return $dateToCompare === $dateToMatch;
                            });
                        }),
                    );
                    echo "{$officialbusiness[0]['purpose']} ";
                }
            }
        @endphp

        @foreach ($holidays as $item)
            @if ($item->month_day === sprintf('%02d-%02d', $month, $i))
                @php
                    echo $item->description;
                @endphp
            @break
        @endif
    @endforeach
</td>
