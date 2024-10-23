@php
    $isHoliday = false;
    $empSched = $Schedule
        ->filter(function ($sched) use ($year, $month, $i) {
            return date('Y-m-d', strtotime($sched->schedule)) ==
                date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
        })
        ->values()
        ->toArray();

    $curDate = date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
    $dtr = array_values(
        array_filter($dtrRecords->toArray(), function ($row) use ($curDate) {
            return $row->dtr_date == $curDate;
        }),
    );

    $cDTR = array_values(
        array_filter($dtrRecords->toArray(), function ($row) use ($curDate) {
            return $row->first_in &&
                $row->first_out &&
                !$row->second_in &&
                !$row->second_out &&
                $row->dtr_date == $curDate;
        }),
    );

    $c1DTR = array_values(
        array_filter($dtrRecords->toArray(), function ($row) use ($curDate) {
            return !$row->first_in &&
                !$row->first_out &&
                $row->second_in &&
                $row->second_out &&
                $row->dtr_date == $curDate;
        }),
    );

@endphp
@foreach ($holidays as $item)
    @if ($item->month_day == sprintf('%02d-%02d', $month, $i))
        @php
            $isHoliday = true;
            $holiday = $item->description;

        @endphp
    @endif
@endforeach

@if (count($cDTR))
    @include('dtr.ViewTwoSched')
@elseif(count($c1DTR))
    @include('dtr.ViewTwoSched', ['firstin_' => true])
@else
    @if ($isHoliday && count($dtr) == 0)
        {{-- HOLIDAY --}}
        @include('dtr.holidayFormat', ['dtr_holiday' => true])
    @else
        @if ($leave_Count || $ot_Count || $ob_Count || $cto_Count)

            @if ($cto_Count && count($ctoApplication))
                @include('dtr.ctoFormat', ['dtr_CTO' => true])
            @else
                <td class="time " colspan="4" id="entry{{ $i }}1">
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
                    @php
                        $yesterDate = date('Y-m-d', strtotime($curDate . ' -1 day'));
                        $yesdtr = array_values(
                            array_filter($dtrRecords->toArray(), function ($row) use ($yesterDate) {
                                return $row->dtr_date == $yesterDate;
                            }),
                        );

                    @endphp
                    <td class="time " style="padding: 2px" id="entry{{ $i }}1">

                        <!--FIRST IN -->
                        @if (count($dtr) && $dtr[0]->first_in)
                            @if (date('a', strtotime($dtr[0]->first_in)) == 'am')
                                {{ date('h:i a', strtotime($dtr[0]->first_in)) }}
                            @else
                                @if (count($yesdtr) == 0)
                                    <span style="font-size:8px;color:rgb(190, 184, 184)">NO ENTRY</span>
                                @endif

                            @endif
                        @else
                            <span style="font-size:8px;color:rgb(190, 184, 184)">NO ENTRY</span>
                        @endif

                    </td>
                    <td class="time " id="entry{{ $i }}2">
                        @if (count($dtr) && $dtr[0]->first_out)
                            @if (date('a', strtotime($dtr[0]->first_out)) == 'am' || date('a', strtotime($dtr[0]->first_out)) == 'pm')
                                {{ date('h:i a', strtotime($dtr[0]->first_out)) }}
                            @endif
                        @else
                            @if (count($yesdtr))
                                @php
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
                                @endphp
                            @else
                                <span style="font-size:8px;color:rgb(177, 166, 166)">NO ENTRY</span>
                            @endif

                        @endif

                    </td>
                    <td class="time " id="entry{{ $i }}3">
                        @if (count($dtr) && $dtr[0]->second_in)
                            @if (date('a', strtotime($dtr[0]->second_in)) == 'pm')
                                {{ date('h:i a', strtotime($dtr[0]->second_in)) }}
                            @endif
                        @else
                            <span style="font-size:8px;color:rgb(177, 166, 166)">NO ENTRY</span>
                        @endif

                    </td>
                    <td class="time " id="entry{{ $i }}4">
                        @if (count($dtr) && $dtr[0]->second_out)
                            @if (date('a', strtotime($dtr[0]->second_out)) == 'pm')
                                {{ date('h:i a', strtotime($dtr[0]->second_out)) }}
                            @endif
                        @else
                            <span style="font-size:8px;color:rgb(177, 166, 166)">NO ENTRY</span>





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
    <td
        style="background-color: whitesmoke;width: 80px !important;border-right: 1px solid rgb(184, 184, 184);padding:10px">
        <span style="font-size:13px;color:gray;">
            @php
                try {
                    if (count($dtr)) {
                        $firstIn = $dtr[0]->first_in ?? null;
                        $firstOut = $dtr[0]->first_out ?? null;

                        $currDate = date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                        if ($firstIn && $firstOut) {
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

                if (count($dtr) == 0 && count($empSched) && $empSched[0]->schedule < date('Y-m-d')) {
                    echo '[Without Leave]';
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
                @break;
            @endif
        @endforeach
</td>

@endif
