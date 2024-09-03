@switch($entry)
    @case('undertime_hours')
        @if (!$leave_Count && !$ot_Count && !$ob_Count && !$cto_Count)
            @php
                $hours = 0;
                $minutes = '';
                $hrs = 0;
            @endphp
            @foreach ($undertime as $ut)
                @if ($biometric_ID == $ut['biometric_ID'])
                    @if (date('d', strtotime($ut['created'])) == $i)
                        @php
                            $uttime = $ut['undertime'];
                            $hours = floor($uttime / 60);
                            $minutes = $uttime % 60;

                            if ($hours >= 1) {
                                $hours = $hours;
                            } else {
                                $hours = 0;
                            }

                            if ($minutes >= 1) {
                                $minutes = $minutes;
                            } else {
                                $minutes = '';
                            }
                            $hrs += $hours;
                        @endphp
                    @endif
                @endif
            @endforeach


            @if ($hrs >= 1)
                <span style="color: black;important;font-weight:bold">
                    {{ $hrs }}
                </span>
            @endif
        @endif
    @break

    @case('undertime_minutes')
        @if (!$leave_Count && !$ot_Count && !$ob_Count && !$cto_Count)
            @php
                $hours = '';
                $minutes = 0;

                $min = 0;
            @endphp
            @foreach ($undertime as $ut)
          
                @if ($biometric_ID == $ut['biometric_ID'])
             
                    @if (date('d', strtotime($ut['created'])) == $i)
                        @php
                            $uttime = $ut['undertime'];
                            $hours = floor($uttime / 60);
                            $minutes = $uttime % 60;

                            if ($hours >= 1) {
                                $hours = $hours;
                            } else {
                                $hours = '';
                            }

                            if ($minutes >= 1) {
                                $minutes = $minutes;
                            } else {
                                $minutes = 0;
                            }
                            $min += $minutes;

                        @endphp
                    @endif
                @endif
            @endforeach
                    @if ($min >= 1)
                        <span style="color: black !important;font-weight:bold">
                            {{ $min }}
                        </span>
                        
                    @endif
            @else 
        
        @endif
    @break

    @default
@endswitch
