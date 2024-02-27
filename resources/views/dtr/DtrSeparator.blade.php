@php
    $absent = false;
@endphp
@switch($entry)
    @case('firstin')
    @php
    $isHoliday = false;
    @endphp


@foreach ($holidays as $item)
    @if ($item->month_day == $month.'-'.$i)
       @php
           $isHoliday = true;
       @endphp

    @endif
@endforeach




    @php
    $countin = 0;
    @endphp

    @foreach($firstin as $key=> $f1)
    @if($biometric_ID  == $f1['biometric_ID'])
    @if($f1['first_in'])
    @if(date('d',strtotime($f1['first_in'])) == $i)
    <span class="fentry">

        {{date('h:i a',strtotime($f1['first_in']))}}

    </span>


    @php
    $countin ++ ;
    @endphp


    @endif
    @endif
    @endif
    @endforeach

    @if(date('D',strtotime(date('Y-m-d',strtotime($year.'-'.$month.'-'.$i)))) == 'Sun'
    )
     @if (!$isHoliday)
     @if ($countin == 0)
             <span class="timefirstarrival" style="color:gray">Day-off </span>
     @endif

    @endif

    @elseif(date('D',strtotime(date('Y-m-d',strtotime($year.'-'.$month.'-'.$i)))) == 'Sat'
    )
    @if($countin == 0)


    @php
        $count2 = 0;
    @endphp


    @foreach ($secondin as $s1)
        @if ($s1['second_in'])
        @if(date('d',strtotime($s1['second_in'])) != $i)
       @php
           $count2 ++;
       @endphp
        @endif

        @endif
    @endforeach


    @if ($count2 >=1)
    <span class="timefirstarrival" style="color:gray">Day-off</span>
    @else
    <span class="timefirstarrival" style="color:gray">Day-off</span>
    @endif
    @endif


    @else
    @if($countin == 0)
    @if(date('Y-m-d',strtotime($year.'-'.$month.'-'.$i)) < date('Y-m-d') )
    @if ($isHoliday)

     {{-- <span style="color:gray">HOLIDAY</span> --}}
    @else
    @php
        $checkSched = $schedule->filter(function($row) use($year,$month,$i){
            return $row['schedule'] === date('Y-m-d',strtotime($year.'-'.$month.'-'.$i));
        });

    @endphp
        @if (count($checkSched)>=1)
        <span class="timefirstarrival" style="color:gray;font-style:italic;color:#FF6969">ABSENT</span>
        @else
        <span class="timefirstarrival" style="color:gray">Day-off</span>
        @endif


    @endif

        @endif
        @endif


        @endif
        @if ($isHoliday)
        @if (!$countin)
        <span class="timefirstarrival" style="color:rgb(5, 128, 42)">HOLIDAY</span>
        @endif
        @endif
        @break
    @case('firstout')
    <span class="fentry">
        <!-- FIRST OUT -->

        @php
        $fo = 0;
            if($fspan){
                $fo = $i + 1;
            }else {
                $fo = $i;
            }
        @endphp
        @foreach($firstout as $f2)
        @if($biometric_ID  == $f2['biometric_ID'])
        @if($f2['first_out'])
        @if(date('d',strtotime($f2['first_out'])) == $fo)
        {{date('h:i a',strtotime($f2['first_out']))}}
        @endif
        @endif
        @endif
        @endforeach
    </span>
        @break
        @case('secondin')

        <span class="fentry">
            <!-- SECOND IN -->
            @foreach($secondin as $f3)
            @if($biometric_ID  == $f3['biometric_ID'])
            @if($f3['second_in'])

            @if(date('d',strtotime($f3['second_in'])) == $i)

            {{date('h:i a',strtotime($f3['second_in']))}}

            @endif
            @endif
            @endif
            @endforeach
        </span>
        @break
        @case('secondout')
        <span class="fentry">
            <!-- SECOND OUT -->
            @foreach($secondout as $f4)
            @if($biometric_ID  == $f4['biometric_ID'])
            @if($f4['second_out'])
            @if(date('d',strtotime($f4['second_out'])) == $i)
            {{date('h:i a',strtotime($f4['second_out']))}}
            @endif

            @endif
            @endif
            @endforeach
        </span>
        @break

        @case('undertime')



        <table style="text-align: center;border:none" >
            <tr style="height:20px" >
                @php
                $hours = '-';
                $minutes = '-';
                @endphp
                @foreach($undertime as $ut)
                @if(date('d',strtotime($ut['created'])) == $i)
                @php
                $uttime = $ut['undertime'];
                $hours = floor($uttime / 60);
                $minutes = $uttime % 60;

                if($hours >=1){
                $hours = $hours;
                }else {
                $hours = '-';
                }

                if($minutes >=1){
                $minutes = $minutes;
                }else {
                $minutes = '-';
                }

                @endphp

                @endif
                @endforeach
                <td class="time" style="border:none;width: 50px;border-right:1px solid rgb(177, 181, 185);font-weight:bold;color:#FF6969;">{{$hours}}</td>
                <td class="time" style=" width: 50px;color:#FF6969;border:none">{{$minutes}}</td>


            </tr>
        </table>
        @break

    @default

@endswitch
