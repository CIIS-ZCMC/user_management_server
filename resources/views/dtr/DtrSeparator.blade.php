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
                            return $row->schedule === date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                        });

                    @endphp
                    @if (count($checkSched) >= 1)
                        <span style="color:gray;font-style:italic;color:#FF6969;font-size:12px">ABSENT</span>

                        <script>
                            $(document).ready(function() {
                                $("#entry{{ $i }}1").addClass("Absent");
                                $("#entry{{ $i }}2").addClass("Absent");
                                $("#entry{{ $i }}3").addClass("Absent");
                                $("#entry{{ $i }}4").addClass("Absent");

                            })
                        </script>
                    @else
                        <span class="timefirstarrival" style="color:gray">Day-off </span>
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


                @if ($count2 >= 1)
                    @php
                        $checkSched = $schedule->filter(function ($row) use ($year, $month, $i) {
                            return $row->schedule === date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                        });

                    @endphp
                    @if (count($checkSched) >= 1)
                        <script>
                            $(document).ready(function() {

                                $("#entry{{ $i }}1").addClass("Absent");
                                $("#entry{{ $i }}2").addClass("Absent");
                                $("#entry{{ $i }}3").addClass("Absent");
                                $("#entry{{ $i }}4").addClass("Absent");
                            })
                        </script>
                        <span style="color:gray;font-style:italic;color:#FF6969;font-size:12px">ABSENT</span>
                    @else
                        <span class="timefirstarrival" style="color:gray">Day-off </span>
                    @endif
                @else
                    <span class="timefirstarrival" style="color:gray">Day-off</span>
                @endif
            @endif
        @else
            @if ($countin == 0)
                @if (date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)) < date('Y-m-d'))
                    @if ($isHoliday)
                        {{-- <span style="color:gray">HOLIDAY</span> --}}
                    @else
                        @php
                            $checkSched = $schedule->filter(function ($row) use ($year, $month, $i) {
                                return $row->schedule === date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                            });

                        @endphp
                        @if (count($checkSched) >= 1)
                            <span class="timefirstarrival" style="color:gray;font-style:italic;color:#FF6969">ABSENT</span>

                            <script>
                                $(document).ready(function() {

                                    $("#entry{{ $i }}1").addClass("Absent");
                                    $("#entry{{ $i }}2").addClass("Absent");
                                    $("#entry{{ $i }}3").addClass("Absent");
                                    $("#entry{{ $i }}4").addClass("Absent");
                                })
                            </script>
                        @else
                            <span class="timefirstarrival" style="color:gray">Day-off</span>
                        @endif
                    @endif

                @endif
            @endif


        @endif
        @if ($isHoliday)
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
                @if ($biometric_ID == $f2['biometric_ID'])
                    @php
                        $empSched = $schedule->filter(function ($sched) use ($f2) {
                            return date('Y-m-d', strtotime($sched->schedule)) ===
                                date('Y-m-d', strtotime($f2['dtr_date'])) &&
                                $sched->second_in === null &&
                                $sched->second_out === null;
                        });

                    @endphp




                    @if (date('d', strtotime($f2['dtr_date'])) == $fo)
                        @if (count($empSched) >= 1)
                            <span style="font-weight: bold;font-size:20px"> -- : -- --</span>
                        @else
                            @if ($f2['first_out'])
                                {{ date('h:i a', strtotime($f2['first_out'])) }}
                            @else
                                <span style="font-weight: bold;font-size:20px"> -- : -- --</span>
                            @endif
                        @endif
                    @endif
                @endif
            @endforeach




        </span>
    @break

    @case('secondin')
        <span class="">
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
                    @if (date('d', strtotime($f3['dtr_date'])) == $i)
                        @if (count($empSched) >= 1)
                            <span style="font-weight: bold;font-size:20px"> -- : -- --</span>
                        @else
                            @if ($f3['second_in'])
                                {{ date('h:i a', strtotime($f3['second_in'])) }}
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





            @foreach ($secondout as $f4)
                @php
                    $empSched = $schedule->filter(function ($sched) use ($f4) {
                        return date('Y-m-d', strtotime($sched->schedule)) ===
                            date('Y-m-d', strtotime($f4['dtr_date'])) &&
                            $sched->second_in === null &&
                            $sched->second_out === null;
                    });
                @endphp



                @if ($biometric_ID == $f4['biometric_ID'])
                    @if (date('d', strtotime($f4['dtr_date'])) == $i)
                        @if (count($empSched) >= 1)
                            {{-- OUTPUT THE FIRStOUT --}}


                            @foreach ($firstout as $f2)
                                @if ($biometric_ID == $f2['biometric_ID'])
                                    @if ($f2['first_out'])
                                        @if (date('d', strtotime($f2['dtr_date'])) == $i)
                                            {{ date('h:i a', strtotime($f2['first_out'])) }} <span
                                                style="font-size:13px;font-weight:normal">(
                                                {{ date('M-d', strtotime($f2['first_out'])) }} )</span>
                                        @endif
                                    @endif
                                @endif
                            @endforeach
                        @else
                            @if ($f4['second_out'])
                                {{ date('h:i a', strtotime($f4['second_out'])) }}
                            @endif
                        @endif
                    @endif
                @endif
            @endforeach




        </span>
    @break

    @case('undertime')
        <table style="text-align: center;border:none">
            <tr style="height:20px">
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
                <td class="time"
                    style="border:none;width: 50px;border-right:1px solid rgb(177, 181, 185);font-weight:bold;color:#FF6969;">
                    {{ $hours }}</td>
                <td class="time" style=" width: 50px;color:#FF6969;border:none">{{ $minutes }}</td>


            </tr>
        </table>
    @break

    @case('remarks')
        <span style="font-size:13px;color:gray">
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
