

@switch($entry)
 

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

 



@default
@endswitch
