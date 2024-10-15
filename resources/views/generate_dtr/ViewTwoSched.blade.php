@if ($isHoliday && count($dtr) == 0)
    {{-- HOLIDAY --}}
    @include('generate_dtr.holidayFormat', ['generate_dtr_holiday' => true])
@else
    @if ($leave_Count || $ot_Count || $ob_Count || $cto_Count)



        @if ($cto_Count)
            @if (isset($firstin_))
                @include('generate_dtr.ctoFormat', ['generate_dtr_CTO1' => true])
            @else
                @include('generate_dtr.ctoFormat', ['generate_dtr_CTO2' => true])
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
            @include('generate_dtr.dayoffFormat', ['generate_dtr_dayoff' => true])
        @else
            @if (count($dtr) == 0 && count($empSched) && $empSched[0]->schedule < date('Y-m-d'))
                @include('generate_dtr.absentFormat', ['generate_dtr_absent' => true])
            @else
                <td class="space" style="width: 50px !important;font-weight:bold" id="entry{{ $i }}1">

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
                    @endif
                </td>
                <td class="space" style="width: 50px !important;font-weight:bold" id="entry{{ $i }}2">


                    @php
                        $yesterDate = date('Y-m-d', strtotime($curDate . ' -1 day'));
                        $yesdtr = array_values(
                            array_filter($dtrRecords->toArray(), function ($row) use ($yesterDate) {
                                return $row->dtr_date == $yesterDate;
                            }),
                        );
                        if (count($yesdtr) >= 1) {
                            if (
                                (date('H', strtotime($yesdtr[0]->first_in)) >= 21 &&
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
                    @endif



                </td>
                <td class="space" style="width: 50px !important;font-weight:bold" id="entry{{ $i }}3">
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
                    @endif

                </td>
                <td class="space" style="width: 50px !important;font-weight:bold" id="entry{{ $i }}4">
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
                    @endif

                </td>
            @endif
        @endif
    @endif
@endif

<td style="width: 40px !important;font-size:10px;height:40px;">
    @include('generate_dtr.DtrSeparator', ['entry' => 'undertime_hours'])
</td>
<td style="width: 40px !important;font-size:10px;">
    @include('generate_dtr.DtrSeparator', ['entry' => 'undertime_minutes'])
</td>
