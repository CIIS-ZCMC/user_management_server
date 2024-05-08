{{-- <tr>
    <td style="font-size:11px !important; border-bottom:none !important " colspan="3" >

            Checking of Ledgers Cash flow preparation

    </td>
    <td style="font-size:11px !important;" colspan="2"  >
        13,000

    </td>
    <td style="font-size:11px !important;" colspan="1" >

    80hours

    </td>
    <td style="font-size:11px !important;" colspan="1">
        Dec 1 to Jan 31
    </td>
    <td style="font-size:11px !important;" colspan="3">

        <div style="position:relative;left:1%;width:200px;">
            Agnes B. Ting, Fritzie Lynn Cabilin, Vanessa De Castro, Anabella Ocampo, Love Santo, Arnie Chelle Montuerto, Robirose Palomo, Gretel Gregorio

        </div>
    </td>
 </tr> --}}

 <tr>
    <td style="font-size:11px !important; border-bottom: 1px solid transparent;" colspan="5">
        {{$item->name}}
    </td>
    <td style="font-size:11px !important; border-bottom: 1px solid transparent;text-align:center" colspan="1">
      {{$item->quantity}}
    </td>
    <td style="font-size:11px !important; border-bottom: 1px solid transparent;text-align:center" colspan="1">

        @php
            // $totalHours = 0;

            // foreach ($item->dates as $date) {
            //     $timeFrom = strtotime($date->time_from);
            //     $timeTo = strtotime($date->time_to);

            //     // Calculate the time difference in seconds
            //     $timeDifference = $timeTo - $timeFrom;

            //     // Convert the time difference to hours
            //     $hours = $timeDifference / (60 * 60); // 1 hour = 60 minutes * 60 seconds

            //     // Add the hours to the total
            //     $totalHours += $hours;
            // }

            // echo "Total hours: " . $totalHours;

            $totalHoursByDate = [];

        foreach ($item->dates as $date) {
            $dateKey = $date->date;
            $timeFrom = strtotime($date->time_from);
            $timeTo = strtotime($date->time_to);
            $timeDifference = $timeTo - $timeFrom;
            $hours = $timeDifference / (60 * 60);
            if (isset($totalHoursByDate[$dateKey])) {
                $totalHoursByDate[$dateKey] += $hours;
            } else {
                $totalHoursByDate[$dateKey] = $hours;
            }
        }
                    $totalh = 0;

            foreach ($totalHoursByDate as $date => $totalHours) {
                $totalh += $totalHours;
            }
            echo "{$totalh} hours";

        @endphp

    </td>
    <td style="font-size:11px !important; border-bottom: 1px solid transparent;text-align:center" colspan="1">

            {{date("M j",strtotime($item->dates[0]->date))}}
        to
            {{date("M j",strtotime($item->dates[count($item->dates)-1]->date))}}

    </td>
    <td style="font-size:11px !important; border-bottom: 1px solid transparent;" colspan="4">
        <div style="position:relative;left:1%;width:200px;">
            @php
            $employeeNames = [];
        @endphp

        @foreach ($item->dates as $dat)
            @foreach ($dat->employees as $emp)
                @php
                    $employeeNames[] = $emp->employee_profile->name;
                @endphp
            @endforeach
        @endforeach

        @php
            $uniqueEmployeeNames = array_unique($employeeNames);
        @endphp

        @foreach ($uniqueEmployeeNames as $name)
            {{ $name }} ,
        @endforeach

        </div>
    </td>
</tr>
