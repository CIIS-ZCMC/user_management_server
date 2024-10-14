
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


      $c1DTR = array_values(array_filter($dtrRecords->toArray(),function($row) use($curDate) {
        return !$row->first_in && !$row->first_out && $row->second_in && $row->second_out && $row->dtr_date == $curDate ;
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
@elseif(count($c1DTR))
@include('generate_dtr.ViewTwoSched',['firstin_'=>true])
@else


@if ($isHoliday && count($dtr)  == 0)
{{-- HOLIDAY --}}
    @include("generate_dtr.holidayFormat",['generate_dtr_holiday'=>true])
@else
@if ($leave_Count || $ot_Count || $ob_Count || $cto_Count)
        @if($cto_Count && count($ctoApplication) )
            @include("generate_dtr.ctoFormat",['generate_dtr_CTO'=>true])
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
  @include("generate_dtr.dayoffFormat",['generate_dtr_dayoff'=>true])
@else

@if (count($dtr) == 0 && count($empSched) && $empSched[0]->schedule < date('Y-m-d'))
 @include("generate_dtr.absentFormat",['generate_dtr_absent'=>true])
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
