
@if ($isHoliday)
{{-- HOLIDAY --}}
<td class="time" style="font-size:10px;" id="entry{{ $i }}1">
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
        <td class="time" colspan="4" id="entry{{ $i }}1">
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
            <td class="time" style="font-size:10px;letter-spacing:1px;color:rgb(146, 140, 140);" id="entry{{ $i }}1">
              DAY OFF
              
            </td>
            <td class="time" id="entry{{ $i }}2">
                
            </td>
            <td class="time" id="entry{{ $i }}3"></td>
            <td class="time" id="entry{{ $i }}4"></td>
        @else
            @if (count($dtr) == 0 && count($empSched) && $empSched[0]->schedule < date('Y-m-d'))
                <td class="time" style="font-size:10px;letter-spacing:5px;color:rgb(204, 114, 114);" id="entry{{ $i }}1">
                    ABSENT
                    <script>
                        $(document).ready(function() {
                            $("#entry{{ $i }}1").addClass("Absent");
                            $("#entry{{ $i }}2").addClass("Absent");
                            $("#entry{{ $i }}3").addClass("Absent");
                            $("#entry{{ $i }}4").addClass("Absent");
                        });
                    </script>
                </td>
                <td class="time" id="entry{{ $i }}2"></td>
                <td class="time" id="entry{{ $i }}3"></td>
                <td class="time" id="entry{{ $i }}4"></td>
            @else
         
                <td class="space" style="width: 50px !important;font-weight:bold"  id="entry{{ $i }}1">
                    
                    <!--FIRST IN -->
                    @if (count($dtr) && $dtr[0]->first_in)
                    
                        @php
                            if($curDate == date('Y-m-d', strtotime($dtr[0]->first_in)) && date('a', strtotime($dtr[0]->first_in)) == "am") {
                                echo date('h:i a', strtotime($dtr[0]->first_in));
                            }
                        @endphp
                    @else
                                @php
                                    $firstin =true;
                                @endphp
                        
                    @endif
                </td>
                <td class="space" style="width: 50px !important;font-weight:bold"  id="entry{{ $i }}2">
              
                    @if (count($dtr) && $dtr[0]->first_out)
                    @php
                        if($curDate == date('Y-m-d', strtotime($dtr[0]->first_out)) && date('a', strtotime($dtr[0]->first_out)) == "am") {
                            echo date('h:i a', strtotime($dtr[0]->first_out));
                        }
                    @endphp
                @else
                                  

                                 
                          
                @endif

       

                </td>
                <td class="space" style="width: 50px !important;font-weight:bold"  id="entry{{ $i }}3">
                    @if (count($dtr) && $dtr[0]->first_in)
                    @php
                        if($curDate == date('Y-m-d', strtotime($dtr[0]->first_in)) && date('a', strtotime($dtr[0]->first_in)) == "pm") {
                            echo date('h:i a', strtotime($dtr[0]->first_in));
                        }
                    @endphp
                @else
                                        @php
                                        $secondin =true;
                                        $previousTimestamp =date('h:i a', strtotime($dtr[0]->first_in));
                                    @endphp
                  
                @endif

                </td>
                <td class="space" style="width: 50px !important;font-weight:bold"  id="entry{{ $i }}4">
                    @if (count($dtr) && $dtr[0]->first_out)
                    @php
                        if($curDate == date('Y-m-d', strtotime($dtr[0]->first_out)) && date('a', strtotime($dtr[0]->first_out)) == "pm") {
                            echo date('h:i a', strtotime($dtr[0]->first_out));
                        }
                    @endphp
                @else
                                    @php
                                    $secondout =true;
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
