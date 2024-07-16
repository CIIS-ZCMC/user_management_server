

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
@include('dtr.ViewTwoSched')
@else 


@if ($isHoliday)
{{-- HOLIDAY --}}
<td  class="time " style="font-size:10px;letter-spacing:5px;color:rgb(214, 164, 0);" id="entry{{ $i }}1">
    <span class="" style="letter-spacing:  5px">{{ $holidayMessage }}</span>
                <script>
                    $(document).ready(function() {
                        $("#entry{{ $i }}1").addClass("Holiday");
                       
                    })
    </script>
</td>
    <td class="time " id="entry{{ $i }}2">
     
    </td>
    <td class="time " id="entry{{ $i }}3">
      
    </td>
    <td class="time " id="entry{{ $i }}4">
      
</td>
@else 

@if ($leave_Count || $ot_Count || $ob_Count || $cto_Count)
<td class="time " colspan="4" id="entry{{ $i }}1">
    
    @if ($leave_Count)
    <span class="" style="font-weight: normal">{{ $leavemessage }}</span>
@elseif ($ot_Count)
    <span class="">{{ $officialTime }}</span>
@elseif ($ob_Count)
    <span class="">{{ $officialBusinessMessage }}</span>
@elseif ($cto_Count)
    <span class="">{{ $ctoMessage }}</span>
@endif
</td>
@else 



    

@if (count($empSched) == 0)
<td class="time "  style="font-size:10px;letter-spacing:1px;color:rgb(146, 140, 140);" id="entry{{ $i }}1">
    DAY OFF

    </td>
    
    <td class="time " id="entry{{ $i }}2">
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

<td class="time "  style="font-size:10px;letter-spacing:5px;color:rgb(204, 114, 114);" id="entry{{ $i }}1">
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

<td class="time " id="entry{{ $i }}2"></td>
<td class="time " id="entry{{ $i }}3"></td>
<td class="time " id="entry{{ $i }}4"></td>
@else 
<td class="time " id="entry{{ $i }}1">
    
    <!--FIRST IN -->
    @if (count($dtr) && $dtr[0]->first_in)
                @if (date('a', strtotime($dtr[0]->first_in)) == "am")
                {{date('h:i a',strtotime($dtr[0]->first_in))}}  
                @else 
                <p><span style="font-size:8px;color:rgb(190, 184, 184)">NO ENTRY</span></p> 
                @endif           
        @else 
        <p><span style="font-size:8px;color:rgb(190, 184, 184)">NO ENTRY</span></p>
    @endif

</td>
<td class="time " id="entry{{ $i }}2">
    @if (count($dtr) && $dtr[0]->first_out)
                @if (date('a', strtotime($dtr[0]->first_out)) == "am" || date('a', strtotime($dtr[0]->first_out)) == "pm")
                {{date('h:i a',strtotime($dtr[0]->first_out))}}   
                @endif
    @else 
    <p><span style="font-size:8px;color:rgb(177, 166, 166)">NO ENTRY</span></p>
@endif

</td>
<td class="time " id="entry{{ $i }}3">
    @if (count($dtr) && $dtr[0]->second_in)
            @if (date('a', strtotime($dtr[0]->second_in)) == "pm")
            {{date('h:i a',strtotime($dtr[0]->second_in))}}   
            @endif
  
    @else 
    <p><span style="font-size:8px;color:rgb(177, 166, 166)">NO ENTRY</span></p>
@endif

</td>
<td class="time " id="entry{{ $i }}4">
    @if (count($dtr) && $dtr[0]->second_out)
                @if (date('a', strtotime($dtr[0]->second_out)) == "pm")
                {{date('h:i a',strtotime($dtr[0]->second_out))}}   
                @endif
 
    @else 
    <p><span style="font-size:8px;color:rgb(177, 166, 166)">NO ENTRY</span></p> 
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

<td style="background-color: whitesmoke;width: 200px !important;">
    <span style="font-size:13px;color:gray">

        @php

             if(count($empSched) == 0){
                echo "[ No Schedule ]";
             }

             if(count($dtr) == 0 && count($empSched) && $empSched[0]->schedule < date('Y-m-d')){
                echo "[Without Leave]";
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
