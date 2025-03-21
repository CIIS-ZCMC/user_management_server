@if (isset($dtr_holiday))

    @php
        $fout = '';
        $yesterDate = date('Y-m-d', strtotime($curDate . ' -1 day'));
        $yesdtr = array_values(
            array_filter($dtrRecords->toArray(), function ($row) use ($yesterDate) {
                return $row->dtr_date == $yesterDate;
            }),
        );
        if (count($yesdtr) >= 1) {
            if (
                (date('H', strtotime($yesdtr[0]->first_in)) >= 18 &&
                    date('H', strtotime($yesdtr[0]->first_in) <= 23)) ||
                date('H') == 0
            ) {
                if (
                    $curDate == date('Y-m-d', strtotime($yesdtr[0]->first_out)) &&
                    date('a', strtotime($yesdtr[0]->first_out)) == 'am'
                ) {
                    $fout = date('h:i a', strtotime($yesdtr[0]->first_out));
                }
            }
        }
    @endphp


    @if (count($yesdtr) >= 1)
        <td class="time " style="font-size:10px;letter-spacing:5px;color:rgb(160, 126, 16);"
            id="entry{{ $i }}1">
            <span class="" style="letter-spacing:  5px">{{ $holidayMessage }}</span>
            <script>
                $(document).ready(function() {
                    $("#entry{{ $i }}1").addClass("Holiday");
                    $("#entry{{ $i }}2").addClass("Holiday");
                    $("#entry{{ $i }}3").addClass("Holiday");
                    $("#entry{{ $i }}4").addClass("Holiday");

                })
            </script>
        </td>
        <td class="time " id="entry{{ $i }}2">
            {{ $fout }}
        </td>
        <td class="time " id="entry{{ $i }}3">

        </td>
        <td class="time " id="entry{{ $i }}4">

        </td>
    @else
        <td class="time " colspan="4" style="font-size:10px;letter-spacing:5px;color:rgb(160, 126, 16);"
            id="entry{{ $i }}1">
            <span class="">{{ $holidayMessage }}</span>
            <script>
                $(document).ready(function() {
                    $("#entry{{ $i }}1").addClass("Holiday");


                })
            </script>
        </td>
    @endif



@endif
