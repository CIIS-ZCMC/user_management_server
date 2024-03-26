@switch($entry)
    @case('firstin')
        @php
            $isHoliday = false;

        @endphp

        @foreach ($holidays as $item)
            @if ($item->month_day == sprintf('%02d-%02d', $month, $i))
                @php
                    $isHoliday = true;
                @endphp
            @endif
        @endforeach




        @php
            $countin = 0;
        @endphp

        @foreach ($firstin as $key => $f1)
            @php

                $empSched = $schedule->filter(function ($sched) use ($f1) {
                    return date('Y-m-d', strtotime($sched->schedule)) === date('Y-m-d', strtotime($f1['dtr_date'])) &&
                        $sched->second_in === null &&
                        $sched->second_out === null;
                });

            @endphp

            @if ($biometric_ID == $f1['biometric_ID'])
                @if ($f1['first_in'])
                    @if (date('d', strtotime($f1['dtr_date'])) == $i)
                        {{-- check if schedule is half , then check the time if its am or pm --}}
                        @if (count($empSched) >= 1)
                            {{-- checktime if its pm --}}
                            @if (date('A', strtotime($f1['first_in'])) == 'AM')
                                @if ($leave_Count || $ot_Count || $ob_Count)
                                    @if ($leave_Count)
                                        <span style="font-size:8px;font-weight:bold">{{ $leavemessage }}</span>
                                    @elseif ($ot_Count)
                                        <span style="font-size:8px;font-weight:bold">{{ $officialTime }}</span>
                                    @elseif ($ob_Count)
                                        <span style="font-size:8px;font-weight:bold">{{ $officialBusinessMessage }}</span>
                                    @endif
                                @else
                                    <span class="fentry">
                                        {{ date('h:i a', strtotime($f1['first_in'])) }}
                                    </span>
                                @endif
                            @endif
                        @else
                            @if ($leave_Count || $ot_Count || $ob_Count)
                                @if ($leave_Count)
                                    <span style="font-size:8px;font-weight:bold">{{ $leavemessage }}</span>
                                @elseif ($ot_Count)
                                    <span style="font-size:8px;font-weight:bold">{{ $officialTime }}</span>
                                @elseif ($ob_Count)
                                    <span style="font-size:8px;font-weight:bold">{{ $officialBusinessMessage }}</span>
                                @endif
                            @else
                                <span class="fentry">
                                    {{ date('h:i a', strtotime($f1['first_in'])) }}
                                </span>
                            @endif
                        @endif



                        @php
                            $countin++;
                        @endphp
                    @endif
                @endif
            @endif
        @endforeach
        @php
            $checkSched = $schedule->filter(function ($row) use ($year, $month, $i) {
                return $row->schedule === date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)) &&
                    $row->attendance_status == 0;
            });

        @endphp





        @if (date('D', strtotime(date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)))) == 'Sun')
            @if (!$isHoliday)
                @if ($countin == 0)

                    @if ($leave_Count || $ot_Count || $ob_Count)
                        @if ($leave_Count)
                            <span style="font-size:8px;font-weight:bold">{{ $leavemessage }}</span>
                        @elseif ($ot_Count)
                            <span style="font-size:8px;font-weight:bold">{{ $officialTime }}</span>
                        @elseif ($ob_Count)
                            <span style="font-size:8px;font-weight:bold">{{ $officialBusinessMessage }}</span>
                        @endif
                    @else
                        @if (count($checkSched) >= 1)
                            <span style="font-size:8px;font-weight:bold">{{ $absentMessage }}</span>
                        @else
                            <span style="font-size:8px;font-weight:bold">{{ $dayoffmessage }}</span>
                        @endif
                    @endif




                @endif

            @endif
        @elseif(date('D', strtotime(date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)))) == 'Sat')
            @if ($countin == 0)


                @php
                    $count2 = 0;
                @endphp


                @foreach ($secondin as $s1)
                    @if ($s1['second_in'])
                        @if (date('d', strtotime($s1['second_in'])) != $i)
                            @php
                                $count2++;
                            @endphp
                        @endif
                    @endif
                @endforeach
                @php
                    $checkSched = $schedule->filter(function ($row) use ($year, $month, $i) {
                        return $row->schedule === date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)) &&
                            $row->attendance_status == 0;
                    });

                @endphp

                @if ($count2 >= 1)
                    @if ($leave_Count || $ot_Count || $ob_Count)
                        @if ($leave_Count)
                            <span style="font-size:8px;font-weight:bold">{{ $leavemessage }}</span>
                        @elseif ($ot_Count)
                            <span style="font-size:8px;font-weight:bold">{{ $officialTime }}</span>
                        @elseif ($ob_Count)
                            <span style="font-size:8px;font-weight:bold">{{ $officialBusinessMessage }}</span>
                        @endif
                    @else
                        @if (count($checkSched) >= 1)
                            <span style=";font-size:8px;font-weight:bold">{{ $absentMessage }}</span>
                        @else
                            <span style="font-size:8px;font-weight:bold">{{ $dayoffmessage }}</span>
                        @endif
                    @endif
                @else
                    @if ($leave_Count || $ot_Count || $ob_Count)
                        @if ($leave_Count)
                            <span style="font-size:8px;font-weight:bold">{{ $leavemessage }}</span>
                        @elseif ($ot_Count)
                            <span style="font-size:8px;font-weight:bold">{{ $officialTime }}</span>
                        @elseif ($ob_Count)
                            <span style="font-size:8px;font-weight:bold">{{ $officialBusinessMessage }}</span>
                        @endif
                    @else
                        @if (count($checkSched) >= 1)
                            <script>
                                $(document).ready(function() {

                                    $("#entry{{ $i }}1").addClass("Absent");
                                    $("#entry{{ $i }}2").addClass("Absent");
                                    $("#entry{{ $i }}3").addClass("Absent");
                                    $("#entry{{ $i }}4").addClass("Absent");
                                })
                            </script>
                            <span class="timefirstarrival"
                                style="color:gray;font-style:italic;color:#FF6969;">{{ $absentMessage }}</span>
                        @else
                            @if (!$isHoliday)
                                <span style="font-size:8px;font-weight:bold">{{ $dayoffmessage }}</span>
                            @endif
                        @endif
                    @endif

                @endif
            @else
            @endif
        @else
            @if ($countin == 0)
                @if (date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)) < date('Y-m-d'))
                    @if ($isHoliday)
                        {{-- <span style="color:gray">HOLIDAY</span> --}}
                    @else
                        @php
                            $checkSched = $schedule->filter(function ($row) use ($year, $month, $i) {
                                return $row->schedule === date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)) &&
                                    $row->attendance_status == 0;
                            });

                            $presentSched = $schedule->filter(function ($row) use ($year, $month, $i) {
                                return $row->schedule === date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)) &&
                                    $row->attendance_status == 1;
                            });

                        @endphp

                        @if ($leave_Count || $ot_Count || $ob_Count)
                            @if ($leave_Count)
                                <span style="font-size:8px;font-weight:bold">{{ $leavemessage }}</span>
                            @elseif ($ot_Count)
                                <span style="font-size:8px;font-weight:bold">{{ $officialTime }}</span>
                            @elseif ($ob_Count)
                                <span style="font-size:8px;font-weight:bold">{{ $officialBusinessMessage }}</span>
                            @endif
                        @else
                            @if (count($checkSched) >= 1)
                                <span style="font-size:8px;font-weight:bold">{{ $absentMessage }} </span>
                            @else
                                @if (count($presentSched) == 0)
                                    <span style="font-size:8px;font-weight:bold">{{ $dayoffmessage }}</span>
                                @endif
                            @endif

                        @endif




                    @endif

                @endif
            @endif


        @endif
        @if ($isHoliday)
            @if (!$countin)
                <span style="font-size:8px;font-weight:bold">{{ $holidayMessage }}</span>
            @endif
        @endif
    @break

    @case('firstout')
        <span class="fentry">
            <!-- FIRST OUT -->

            @php
                $fo = 0;
                if ($fspan) {
                    $fo = $i + 1;
                } else {
                    $fo = $i;
                }

            @endphp
            @foreach ($firstout as $f2)
                @php

                    $empSched = $schedule->filter(function ($sched) use ($f2) {
                        return date('Y-m-d', strtotime($sched->schedule)) ===
                            date('Y-m-d', strtotime($f2['dtr_date'])) &&
                            $sched->second_in === null &&
                            $sched->second_out === null;
                    });

                    // echo count($empSched);

                @endphp


                @if ($biometric_ID == $f2['biometric_ID'])
                    @if ($f2['first_out'])
                        @if (!$leave_Count && !$ot_Count && !$ob_Count)
                            @if (count($empSched) >= 1)
                                @if (date('d', strtotime($f2['first_out'])) == $fo)
                                    @if (date('A', strtotime($f2['first_out'])) == 'AM')
                                        {{ date('h:i a', strtotime($f2['first_out'])) }}
                                    @endif
                                @endif
                            @else
                                @if (date('d', strtotime($f2['dtr_date'])) == $fo)
                                    @if (date('A', strtotime($f2['first_out'])) == 'PM')
                                        {{ date('h:i a', strtotime($f2['first_out'])) }}
                                    @else
                                        {{ date('h:i a', strtotime($f2['first_out'])) }}
                                    @endif
                                @endif
                            @endif
                        @endif
                    @endif
                @endif
            @endforeach
        </span>
    @break

    @case('secondin')
        <span class="fentry">
            <!-- SECOND IN -->


            @php

                $filteredSecondin = array_filter($secondin, function ($row) use ($i) {
                    return date('d', strtotime($row['dtr_date'])) == $i;
                });

                $filteredSecondin = array_slice($filteredSecondin, 0, 1);

            @endphp

            @foreach ($filteredSecondin as $f3)
                @php

                    $empSched = $schedule->filter(function ($sched) use ($f3) {
                        return date('Y-m-d', strtotime($sched->schedule)) ===
                            date('Y-m-d', strtotime($f3['dtr_date'])) &&
                            $sched->second_in === null &&
                            $sched->second_out === null;
                    });
                @endphp



                @if ($biometric_ID == $f3['biometric_ID'])
                    @if (date('d', strtotime($f3['dtr_date'])) == $i)
                        @if (!$leave_Count && !$ot_Count && !$ob_Count)
                            @if (count($empSched) >= 1)
                                @php
                                    $firsti = array_filter($firstin, function ($res) use ($i) {
                                        return date('d', strtotime($res['dtr_date'])) == $i &&
                                            date('A', strtotime($res['first_in'])) == 'PM';
                                    });

                                @endphp


                                @if (count($firsti) >= 1)
                                    {{ date('h:i a', strtotime(array_values($firsti)[count($firsti) - 1]['first_in'])) }}
                                @else
                                    @foreach ($firstin as $key => $f1)
                                        @if (date('d', strtotime($f1['dtr_date'])) == $i)
                                            @if (date('A', strtotime($f1['first_in'])) == 'PM')
                                                {{ date('h:i a', strtotime($f1['first_in'])) }}
                                            @endif
                                        @endif
                                    @endforeach
                                @endif
                            @else
                                @if ($f3['second_in'])
                                    {{ date('h:i a', strtotime($f3['second_in'])) }}
                                @else
                                    {{-- @foreach ($firstin as $f1)
                                    @if (date('A', strtotime($f1['first_in'])) == 'PM')
                                        @if ($biometric_ID == $f1['biometric_ID'])
                                            @if (date('d', strtotime($f1['dtr_date'])) == $i)
                                                {{ date('h:i a', strtotime($f1['first_in'])) }}
                                            @endif
                                        @endif
                                    @endif
                                @endforeach --}}
                                @endif
                            @endif
                        @endif
                    @endif
                @endif
            @endforeach
        </span>
    @break

    @case('secondout')
        <span class="fentry">
            <!-- SECOND OUT -->

            @php

                $filteredSecondout = array_filter($secondout, function ($row) use ($i, $biometric_ID) {
                    return date('d', strtotime($row['dtr_date'])) == $i && $row['biometric_ID'] == $biometric_ID;
                });

            @endphp

            @foreach ($filteredSecondout as $f4)
                @php

                    $empSched = $schedule->filter(function ($sched) use ($f4) {
                        return date('Y-m-d', strtotime($sched->schedule)) ===
                            date('Y-m-d', strtotime($f4['dtr_date'])) &&
                            $sched->second_in === null &&
                            $sched->second_out === null;
                    });

                @endphp


                @if ($biometric_ID === $f4['biometric_ID'])
                    @if (!$leave_Count && !$ot_Count && !$ob_Count)
                        @if (count($empSched) >= 1)
                            @php
                                $firsto = array_filter($firstout, function ($res) use ($i, $biometric_ID) {
                                    return date('d', strtotime($res['first_out'])) == $i &&
                                        date('A', strtotime($res['first_out'])) === 'PM' &&
                                        $res['biometric_ID'] == $biometric_ID;
                                });

                            @endphp


                            @if (count($firsto) >= 1)
                                {{ date('h:i a', strtotime(array_values($firsto)[count($firsto) - 1]['first_out'])) }}
                            @else
                                @foreach ($firstout as $f2)
                                    @if (date('d', strtotime($f2['first_out'])) == $i)
                                        @if ($biometric_ID === $f2['biometric_ID'])
                                            @if (date('A', strtotime($f2['first_out'])) === 'PM')
                                                {{ date('h:i a', strtotime($f2['first_out'])) }}
                                            @endif
                                        @endif
                                    @endif
                                @endforeach
                            @endif
                        @else
                            @if ($f4['second_out'])
                                @if (date('d', strtotime($f4['dtr_date'])) == $i)
                                    {{ date('h:i a', strtotime($f4['second_out'])) }}
                                @endif
                            @endif
                        @endif
                    @endif
                @endif
            @endforeach
        </span>
    @break

    @case('undertime_hours')
        @if (!$leave_Count && !$ot_Count && !$ob_Count)
            <table style="border:none">
                <tr style="border:none">
                    @php
                        $hours = 0;
                        $minutes = '';
                        $hrs = 0;
                    @endphp
                    @foreach ($undertime as $ut)
                        @if ($biometric_ID == $ut['biometric_ID'])
                            @if (date('d', strtotime($ut['created'])) == $i)
                                @php
                                    $uttime = $ut['undertime'];
                                    $hours = floor($uttime / 60);
                                    $minutes = $uttime % 60;

                                    if ($hours >= 1) {
                                        $hours = $hours;
                                    } else {
                                        $hours = 0;
                                    }

                                    if ($minutes >= 1) {
                                        $minutes = $minutes;
                                    } else {
                                        $minutes = '';
                                    }
                                    $hrs += $hours;
                                @endphp
                            @endif
                        @endif
                    @endforeach
                    @if ($hrs >= 1)
                        <span style="color: black">
                            {{ $hrs }}
                        </span>
                    @endif
                </tr>
            </table>
        @endif
    @break

    @case('undertime_minutes')
        @if (!$leave_Count && !$ot_Count && !$ob_Count)
            @php
                $hours = '';
                $minutes = 0;

                $min = 0;
            @endphp
            @foreach ($undertime as $ut)
                @if ($biometric_ID == $ut['biometric_ID'])
                    @if (date('d', strtotime($ut['created'])) == $i)
                        @php
                            $uttime = $ut['undertime'];
                            $hours = floor($uttime / 60);
                            $minutes = $uttime % 60;

                            if ($hours >= 1) {
                                $hours = $hours;
                            } else {
                                $hours = '';
                            }

                            if ($minutes >= 1) {
                                $minutes = $minutes;
                            } else {
                                $minutes = 0;
                            }
                            $min += $minutes;

                        @endphp
                    @endif
                @endif
            @endforeach
            @if ($min >= 1)
                <span style="color: black !important;font-weight:bold">
                    {{ $min }}
                </span>
            @endif
        @endif
    @break

    @default
@endswitch
