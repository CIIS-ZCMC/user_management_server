
@if(isset($generate_dtr_CTO))

  
        @php
        $ctoApplication = array_values($ctoApplication);
        @endphp
  
    
    @if(isset($ctoApplication[0]) && !$ctoApplication[0]['is_am'] && !$ctoApplication[0]['is_pm'])

           <td class="time " style="font-size:10px;font-weight:bold" colspan="4" id="entry{{ $i }}1">
        {{$ctoMessage}} ( 8 hrs )
       
            </td>
 
        
    @elseif(isset($ctoApplication[0]) && $ctoApplication[0]['is_am'] && !$ctoApplication[0]['is_pm'])
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
    @elseif ( isset($ctoApplication[0]) && !$ctoApplication[0]['is_am'] && $ctoApplication[0]['is_pm'])
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
                <td class="time " style="font-size:10px;font-weight:bold" colspan="2" id="entry{{ $i }}4">
                    {{$ctoMessage}} ( 4 hrs )
                </td>


 @else
            <td class="time " style="font-size:10px;font-weight:bold" colspan="4" id="entry{{ $i }}4">
                 {{$ctoMessage}} ( 8 hrs )
            </td>
 @endif


@endif

@if(isset($generate_dtr_CTO1))

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
      
@endif




@if(isset($generate_dtr_CTO2))

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
          
         <td class="time " style="font-size:10px;font-weight:bold" colspan="2" id="entry{{ $i }}3">
                 {{$ctoMessage}} ( 4 hrs )
            </td>
          
      
@endif