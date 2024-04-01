@php
    $absent = false;
    $holiday = '';
@endphp
@switch($entry)
    @case('firstin')
        @php
            $isHoliday = false;
        @endphp


        @foreach ($holidays as $item)
            @if ($item->month_day == sprintf('%02d-%02d', $month, $i))
                @php
                    $isHoliday = true;
                    $holiday = $item->description;
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
                        <span class="fentry">
                            @if (date('A', strtotime($f1['first_in'])) == 'AM')
                                @if ($leave_Count || $ot_Count || $ob_Count || $cto_Count)
                                    @if ($leave_Count)
                                        <span class="timefirstarrival">{{ $leavemessage }}</span>
                                    @elseif ($ot_Count)
                                        <span class="timefirstarrival">{{ $officialTime }}</span>
                                    @elseif ($ob_Count)
                                        <span class="timefirstarrival">{{ $officialBusinessMessage }}</span>
                                    @elseif ($cto_Count)
                                        <span class="timefirstarrival">{{ $ctoMessage }}</span>
                                    @endif
                                @else
                                    <span class="fentry">
                                        {{ date('h:i a', strtotime($f1['first_in'])) }}
                                    </span>
                                    <script>
                                        $(document).ready(function() {
                                            $("#entry{{ $i }}1").addClass("Present");
                                            $("#entry{{ $i }}2").addClass("Present");
                                            $("#entry{{ $i }}3").addClass("Present");
                                            $("#entry{{ $i }}4").addClass("Present");

                                        })
                                    </script>
                                @endif
                            @endif


                        </span>


                        @php
                            $countin++;
                        @endphp
                    @endif
                @endif
            @endif
        @endforeach

        @if (date('D', strtotime(date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)))) == 'Sun')
            @if (!$isHoliday)
                @if ($countin == 0)
                    @php
                        $checkSched = $schedule->filter(function ($row) use ($year, $month, $i) {
                            return $row->schedule === date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)) &&
                                $row->attendance_status == 0;
                        });

                    @endphp
                    @if ($leave_Count || $ot_Count || $ob_Count || $cto_Count)
                        @if ($leave_Count)
                            <span class="timefirstarrival">{{ $leavemessage }}</span>
                        @elseif ($ot_Count)
                            <span class="timefirstarrival">{{ $officialTime }}</span>
                        @elseif ($ob_Count)
                            <span class="timefirstarrival">{{ $officialBusinessMessage }}</span>
                        @elseif ($cto_Count)
                            <span class="timefirstarrival">{{ $ctoMessage }}</span>
                        @endif
                    @else
                        @if (count($checkSched) >= 1)
                            <span class="fentry">
                                <span class="timefirstarrival" style="color:#FF6969;">{{ $absentMessage }}</span>

                                <script>
                                    $(document).ready(function() {
                                        $("#entry{{ $i }}1").addClass("Absent");
                                        $("#entry{{ $i }}2").addClass("Absent");
                                        $("#entry{{ $i }}3").addClass("Absent");
                                        $("#entry{{ $i }}4").addClass("Absent");

                                    })
                                </script>
                            </span>
                        @else
                            <span class="timefirstarrival" style="color:gray">{{ $dayoffmessage }}</span>
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
                        @if (date('d', strtotime($s1['dtr_date'])) != $i)
                            @php
                                $count2++;
                            @endphp
                        @endif
                    @endif
                @endforeach
                <span class="fentry">
                    @php
                        $checkSched = $schedule->filter(function ($row) use ($year, $month, $i) {
                            return $row->schedule === date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)) &&
                                $row->attendance_status == 0;
                        });

                    @endphp
                    @if ($count2 >= 1)
                        @if ($leave_Count || $ot_Count || $ob_Count || $cto_Count)
                            @if ($leave_Count)
                                <span class="timefirstarrival">{{ $leavemessage }}</span>
                            @elseif ($ot_Count)
                                <span class="timefirstarrival">{{ $officialTime }}</span>
                            @elseif ($ob_Count)
                                <span class="timefirstarrival">{{ $officialBusinessMessage }}</span>
                            @elseif ($cto_Count)
                                <span class="timefirstarrival">{{ $ctoMessage }}</span>
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
                                <span class="timefirstarrival" style="color:gray">{{ $dayoffmessage }}</span>
                            @endif
                        @endif
                    @else
                        @if ($leave_Count || $ot_Count || $ob_Count || $cto_Count)
                            @if ($leave_Count)
                                <span class="timefirstarrival">{{ $leavemessage }}</span>
                            @elseif ($ot_Count)
                                <span class="timefirstarrival">{{ $officialTime }}</span>
                            @elseif ($ob_Count)
                                <span class="timefirstarrival">{{ $officialBusinessMessage }}</span>
                            @elseif ($cto_Count)
                                <span class="timefirstarrival">{{ $ctoMessage }}</span>
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
                                    <span class="timefirstarrival" style="color:gray">{{ $dayoffmessage }}</span>
                                @endif
                            @endif
                        @endif

                    @endif
                </span>
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
                        <span class="fentry">
                            @if (count($checkSched) >= 1)
                                @if ($leave_Count || $ot_Count || $ob_Count || $cto_Count)
                                    @if ($leave_Count)
                                        <span class="timefirstarrival">{{ $leavemessage }}</span>
                                    @elseif ($ot_Count)
                                        <span class="timefirstarrival">{{ $officialTime }}</span>
                                    @elseif ($ob_Count)
                                        <span class="timefirstarrival">{{ $officialBusinessMessage }}</span>
                                    @elseif ($cto_Count)
                                        <span class="timefirstarrival">{{ $ctoMessage }}</span>
                                    @endif
                                @else
                                    <span class="timefirstarrival"
                                        style="color:gray;font-style:italic;color:#FF6969">{{ $absentMessage }}</span>

                                    <script>
                                        $(document).ready(function() {

                                            $("#entry{{ $i }}1").addClass("Absent");
                                            $("#entry{{ $i }}2").addClass("Absent");
                                            $("#entry{{ $i }}3").addClass("Absent");
                                            $("#entry{{ $i }}4").addClass("Absent");
                                        })
                                    </script>
                                @endif
                            @else
                                @if (count($presentSched) == 0)
                                    <span class="timefirstarrival" style="color:gray;">{{ $dayoffmessage }}</span>
                                @else
                                    <script>
                                        $(document).ready(function() {
                                            $("#entry{{ $i }}1").addClass("Present");
                                            $("#entry{{ $i }}2").addClass("Present");
                                            $("#entry{{ $i }}3").addClass("Present");
                                            $("#entry{{ $i }}4").addClass("Present");

                                        })
                                    </script>
                                @endif
                            @endif
                        </span>


                    @endif

                @endif
            @endif


        @endif
        @if ($isHoliday)
            <span class="fentry">
                @if (!$countin)
                    <span class="timefirstarrival" style="color:rgb(112, 82, 0); font-weight: normal">Holiday</span>
                    <script>
                        $(document).ready(function() {
                            $("#entry{{ $i }}1").addClass("Holiday");
                            $("#entry{{ $i }}2").addClass("Holiday");
                            $("#entry{{ $i }}3").addClass("Holiday");
                            $("#entry{{ $i }}4").addClass("Holiday");
                        })
                    </script>
                @endif
        @endif
        </span>
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
                        @if (!$leave_Count && !$ot_Count && !$ob_Count && !$cto_Count)
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



            @foreach ($secondin as $f3)
                @php

                    $empSched = $schedule->filter(function ($sched) use ($f3) {
                        return date('Y-m-d', strtotime($sched->schedule)) ===
                            date('Y-m-d', strtotime($f3['dtr_date'])) &&
                            $sched->second_in === null &&
                            $sched->second_out === null;
                    });

                @endphp

                @if ($biometric_ID == $f3['biometric_ID'])
                    @if (date('d', strtotime($f3['second_in'])) == $i)
                        @if (!$leave_Count && !$ot_Count && !$ob_Count && !$cto_Count)
                            @if (date('A', strtotime($f3['second_in'])) === 'PM')
                                @if ($f3['second_in'])
                                    {{ date('h:i a', strtotime($f3['second_in'])) }}
                                @endif
                            @endif
                        @endif
                    @endif
                @endif
            @endforeach


            @foreach ($firstin as $key => $f1)
                @if ($biometric_ID == $f1['biometric_ID'])
                    @if (date('d', strtotime($f1['first_in'])) == $i)
                        @if (!$leave_Count && !$ot_Count && !$ob_Count && !$cto_Count)
                            @if (date('A', strtotime($f1['first_in'])) === 'PM')
                                @if ($f1['first_in'])
                                    {{ date('h:i a', strtotime($f1['first_in'])) }}
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
                                    @if (!$leave_Count && !$ot_Count && !$ob_Count && !$cto_Count)
                                        @if ($biometric_ID === $f2['biometric_ID'])
                                            @if (date('A', strtotime($f2['first_out'])) === 'PM')
                                                {{ date('h:i a', strtotime($f2['first_out'])) }}
                                            @endif
                                        @endif
                                    @endif
                                @endif
                            @endforeach
                        @endif
                    @else
                        @if ($f4['second_out'])
                            @if (date('d', strtotime($f4['dtr_date'])) == $i)
                                @if (!$leave_Count && !$ot_Count && !$ob_Count && !$cto_Count)
                                    {{ date('h:i a', strtotime($f4['second_out'])) }}
                                @endif
                            @endif
                        @endif
                    @endif
                @endif
            @endforeach
        </span>
    @break

    @case('undertime')
        <table style="text-align: center;border:none">
            <tr style="height:10px">

                @php
                    $hours = '-';
                    $minutes = '-';
                @endphp
                @foreach ($undertime as $ut)
                    @if (date('d', strtotime($ut['created'])) == $i)
                        @php
                            $uttime = $ut['undertime'];
                            $hours = floor($uttime / 60);
                            $minutes = $uttime % 60;

                            if ($hours >= 1) {
                                $hours = $hours;
                            } else {
                                $hours = '-';
                            }

                            if ($minutes >= 1) {
                                $minutes = $minutes;
                            } else {
                                $minutes = '-';
                            }

                        @endphp
                    @endif
                @endforeach
                <td class=""
                    style="border:none;width: 50px;border-right:1px solid rgb(177, 181, 185);font-weight:bold;color:#FF6969;font-size:12px">
                    @if (!$leave_Count && !$ot_Count && !$ob_Count && !$cto_Count)
                        {{ $hours }}
                    @else
                        -
                    @endif
                </td>
                <td class="" style=" width: 50px;color:#FF6969;border:none;font-size:12px">
                    @if (!$leave_Count && !$ot_Count && !$ob_Count && !$cto_Count)
                        {{ $minutes }}
                    @else
                        -
                    @endif
                </td>

            </tr>
        </table>
    @break

    @case('wsched')
        @php

            $empSched = $schedule->filter(function ($sched) use ($year, $month, $i) {
                return date('Y-m-d', strtotime($sched->schedule)) ==
                    date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
            });
        @endphp
        @if (count($empSched) >= 1)
            <span style="font-size: 11px;color:gray">
                @if (!$empSched->first()->second_in && !$empSched->first()->second_out)
                    {{ date('G', strtotime($empSched->first()->first_in)) }} -
                    {{ date('G', strtotime($empSched->first()->first_out)) }}
                @else
                    {{ date('G', strtotime($empSched->first()->first_in)) }} -
                    {{ date('G', strtotime($empSched->first()->first_out)) }} |
                    {{ date('G', strtotime($empSched->first()->second_in)) }} -
                    {{ date('G', strtotime($empSched->first()->second_out)) }}
                @endif

            </span>
            <script>
                $("#wsched{{ $i }}").addClass("wsched");
            </script>
        @else
        @endif
    @break

    @case('remarks')
        <span style="font-size:13px;color:gray">

            @php

                $empSched = $schedule->filter(function ($sched) use ($year, $month, $i) {
                    return date('Y-m-d', strtotime($sched->schedule)) ==
                        date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                });
                /**
                 * Display only if theres schedule
                 *
                 */

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

                    echo "{$leave[0]['city']}, {$leave[0]['country']} ";
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
                @break;
            @endif
        @endforeach
    </span>
@break

@default
@endswitch
