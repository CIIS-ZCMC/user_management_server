
<style>
    .space {
        width: 500px !important;
        height: 30px !important;
        font-size: 9px !important;
    }
</style>
@php
$isHoliday = false;
$empSched = $Schedule->filter(function ($sched) use ($year, $month, $i) {
    return date('Y-m-d', strtotime($sched->schedule)) ==
        date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
})->values()->toArray();

        $curDate = date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
    $dtr = array_values(array_filter($dtrRecords->toArray(),function($row) use($curDate){
        return $row->dtr_date == $curDate ;
    }));

    $cDTR = array_values(array_filter($dtrRecords->toArray(),function($row) use($curDate) {
        return $row->first_in && $row->first_out && !$row->second_in && !$row->second_out && $row->dtr_date == $curDate ;
    }));


@endphp
@foreach ($holidays as $item)
@if ($item->month_day == sprintf('%02d-%02d', $month, $i))
    @php
        $isHoliday = true;
        $holiday = $item->description;

    @endphp
@endif
@endforeach

@if(count($cDTR))

@include('generate_dtr.ViewTwoSched')
@else


@if ($isHoliday && count($dtr)  == 0)
{{-- HOLIDAY --}}
<td  class="time " colspan="4" style="font-size:10px;font-weight:bold" id="entry{{ $i }}1">
    <span class="" >{{ $holidayMessage }}</span>
                <script>
                    $(document).ready(function() {
                        $("#entry{{ $i }}1").addClass("Holiday");

                    })
    </script>
</td>

@else

@if ($leave_Count || $ot_Count || $ob_Count || $cto_Count)
@if($cto_Count )

    @if($ctoApplication[0]['is_am'])
    <td class="time " style="font-size:10px;font-weight:bold" colspan="2" id="entry{{ $i }}1">
        {{$ctoMessage}} ( 4 hrs )
    </td>
    <td  class="space" style="width: 50px !important;font-weight:bold" id="entry{{ $i }}3">
        @if (count($dtr) && $dtr[0]->second_in)
                @if (date('a', strtotime($dtr[0]->second_in)) == "pm")
                {{date('h:i a',strtotime($dtr[0]->second_in))}}
                @else
                <span>-</span>
           @endif

        @else

    @endif

    </td>
    <td  class="space" style="width: 50px !important;font-weight:bold" id="entry{{ $i }}4">
        @if (count($dtr) && $dtr[0]->second_out)
                    @if (date('a', strtotime($dtr[0]->second_out)) == "pm")
                    {{date('h:i a',strtotime($dtr[0]->second_out))}}
                    @else
                         <span>-</span>
                    @endif

        @else

    @endif
    </td>
    @elseif($ctoApplication[0]['is_pm'])
    <td  class="space" style="width: 50px !important;font-weight:bold" id="entry{{ $i }}1">
        <!--FIRST IN -->
        @if (count($dtr) && $dtr[0]->first_in)
                    @if (date('a', strtotime($dtr[0]->first_in)) == "am")
                    {{date('h:i a',strtotime($dtr[0]->first_in))}}
                    @else
                    @if (count($yesdtr) == 0)
                         <span>-</span>
                         @endif
                    @endif
            @else
            <span>-</span>
        @endif
    </td>
    <td  class="space" style="width: 50px !important;font-weight:bold" id="entry{{ $i }}2">
        @if (count($dtr) && $dtr[0]->first_out)
        @if (date('a', strtotime($dtr[0]->first_out)) == "am" || date('a', strtotime($dtr[0]->first_out)) == "pm")
        {{date('h:i a',strtotime($dtr[0]->first_out))}}
        @endif
    @else

    @if (count($yesdtr))
    @php
    if (date('H',strtotime($yesdtr[0]->first_in)) >= 18 && date('H',strtotime($yesdtr[0]->first_in) <= 23) || date('H') == 0) {
    if($curDate == date('Y-m-d', strtotime($yesdtr[0]->first_out)) && date('a', strtotime($yesdtr[0]->first_out)) == "am") {
            echo date('h:i a', strtotime($yesdtr[0]->first_out));
    }
    }
    @endphp
    @else

    <span>-</span>
    @endif

    @endif

    </td>
    <td class="time " style="font-size:10px;font-weight:bold" colspan="2" id="entry{{ $i }}1">
        {{$ctoMessage}} ( 4 hrs )
    </td>
    @else
    <td class="time " style="font-size:10px;font-weight:bold" colspan="4" id="entry{{ $i }}1">
        {{$ctoMessage}}
    </td>

    @endif

    @else
    <td class="time " style="font-size:10px;font-weight:bold" colspan="4" id="entry{{ $i }}1">
        @if ($leave_Count)
           <span class="" >{{ $leavemessage }}</span>
       @elseif ($ot_Count)
           <span class="">{{ $officialTime }}</span>
       @elseif ($ob_Count)
           <span class="">{{ $officialBusinessMessage }}</span>
       @endif
       </td>
@endif


@else





@if (count($empSched) == 0)
<td class="time " style="font-size:10px;font-weight:bold" id="entry{{ $i }}1">
    DAY OFF

    </td>

    <td class="time " style="font-weight:bold" id="entry{{ $i }}2">
        @php
            $yesterDate = date('Y-m-d', strtotime($curDate." -1 day"));
            $yesdtr = array_values(array_filter($dtrRecords->toArray(),function($row) use($yesterDate){
        return $row->dtr_date == $yesterDate ;
            }));


        if(count($yesdtr)>=1){

                if (date('H',strtotime($yesdtr[0]->first_in)) >= 18 && date('H',strtotime($yesdtr[0]->first_in) <= 23) || date('H') == 0) {
                    if($curDate == date('Y-m-d', strtotime($yesdtr[0]->first_out)) && date('a', strtotime($yesdtr[0]->first_out)) == "am") {
                            echo date('h:i a', strtotime($yesdtr[0]->first_out));
                    }
                }
        }



        @endphp

        {{-- @if (count($dtr) && $dtr[0]->first_out)
                    @php
                        if($curDate == date('Y-m-d', strtotime($dtr[0]->first_out)."-1 day") && date('a', strtotime($dtr[0]->first_out)) == "am") {
                            echo date('h:i a', strtotime($dtr[0]->first_out));
                        }
                    @endphp
        @else
                    @php
                    $secondin =true;
                    $previousTimestamp =date('h:i a', strtotime($dtr[0]->first_in));
                     @endphp
                  <p><span style="font-size:8px;color:rgb(190, 184, 184)">NO ENTRY</span></p>
        @endif --}}



    </td>
    <td class="time " id="entry{{ $i }}3">

    </td>
    <td class="time " id="entry{{ $i }}4">

    </td>
@else

@if (count($dtr) == 0 && count($empSched) && $empSched[0]->schedule < date('Y-m-d'))

<td class="time "  style="font-size:10px;font-weight:bold" id="entry{{ $i }}1">
ABSENT
 <script>
    $(document).ready(function() {
        $("#entry{{ $i }}1").addClass("Absent");
        $("#entry{{ $i }}2").addClass("Absent");
        $("#entry{{ $i }}3").addClass("Absent");
        $("#entry{{ $i }}4").addClass("Absent");

    })
</script>
</td>

<td class="time " style="font-weight:bold" id="entry{{ $i }}2">
    @php
    $yesterDate = date('Y-m-d', strtotime($curDate." -1 day"));
    $yesdtr = array_values(array_filter($dtrRecords->toArray(),function($row) use($yesterDate){
return $row->dtr_date == $yesterDate ;
    }));


if(count($yesdtr)>=1){

        if (date('H',strtotime($yesdtr[0]->first_in)) >= 21 && date('H',strtotime($yesdtr[0]->first_in) <= 23) || date('H') == 0) {
            if($curDate == date('Y-m-d', strtotime($yesdtr[0]->first_out)) && date('a', strtotime($yesdtr[0]->first_out)) == "am") {
                    echo date('h:i a', strtotime($yesdtr[0]->first_out));
            }
        }
}
@endphp
</td>
<td class="time " id="entry{{ $i }}3"></td>
<td class="time " id="entry{{ $i }}4"></td>
@else


@php
    $yesterDate = date('Y-m-d', strtotime($curDate." -1 day"));
    $yesdtr = array_values(array_filter($dtrRecords->toArray(),function($row) use($yesterDate){
return $row->dtr_date == $yesterDate ;
    }));


@endphp
<td  class="space" style="width: 50px !important;font-weight:bold" id="entry{{ $i }}1">
    <!--FIRST IN -->
    @if (count($dtr) && $dtr[0]->first_in)
                @if (date('a', strtotime($dtr[0]->first_in)) == "am")
                {{date('h:i a',strtotime($dtr[0]->first_in))}}
                @else
                @if (count($yesdtr) == 0)
                     <span>-</span>
                     @endif
                @endif
        @else
        <span>-</span>
    @endif
</td>
<td  class="space" style="width: 50px !important;font-weight:bold" id="entry{{ $i }}2">
    @if (count($dtr) && $dtr[0]->first_out)
    @if (date('a', strtotime($dtr[0]->first_out)) == "am" || date('a', strtotime($dtr[0]->first_out)) == "pm")
    {{date('h:i a',strtotime($dtr[0]->first_out))}}
    @endif
@else

@if (count($yesdtr))
@php
if (date('H',strtotime($yesdtr[0]->first_in)) >= 18 && date('H',strtotime($yesdtr[0]->first_in) <= 23) || date('H') == 0) {
if($curDate == date('Y-m-d', strtotime($yesdtr[0]->first_out)) && date('a', strtotime($yesdtr[0]->first_out)) == "am") {
        echo date('h:i a', strtotime($yesdtr[0]->first_out));
}
}
@endphp
@else

<span>-</span>
@endif

@endif

</td>
<td  class="space" style="width: 50px !important;font-weight:bold" id="entry{{ $i }}3">
    @if (count($dtr) && $dtr[0]->second_in)
            @if (date('a', strtotime($dtr[0]->second_in)) == "pm")
            {{date('h:i a',strtotime($dtr[0]->second_in))}}
            @else
            <span>-</span>
       @endif

    @else

@endif

</td>
<td  class="space" style="width: 50px !important;font-weight:bold" id="entry{{ $i }}4">
    @if (count($dtr) && $dtr[0]->second_out)
                @if (date('a', strtotime($dtr[0]->second_out)) == "pm")
                {{date('h:i a',strtotime($dtr[0]->second_out))}}
                @else
                     <span>-</span>
                @endif

    @else

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

@endif
