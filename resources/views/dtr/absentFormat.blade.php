@if (isset($dtr_absent))


    <td class="time " style="font-size:10px;font-weight:bold" id="entry{{ $i }}1">
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
            $yesterDate = date('Y-m-d', strtotime($curDate . ' -1 day'));
            $yesdtr = array_values(
                array_filter($dtrRecords->toArray(), function ($row) use ($yesterDate) {
                    return $row->dtr_date == $yesterDate;
                }),
            );

            if (count($yesdtr) >= 1) {
                if (
                    (date('H', strtotime($yesdtr[0]->first_in)) >= 21 &&
                        date('H', strtotime($yesdtr[0]->first_in) <= 23)) ||
                    date('H') == 0
                ) {
                    if (
                        $curDate == date('Y-m-d', strtotime($yesdtr[0]->first_out)) &&
                        date('a', strtotime($yesdtr[0]->first_out)) == 'am'
                    ) {
                        echo date('h:i a', strtotime($yesdtr[0]->first_out));
                    }
                }
            }
        @endphp
    </td>
    <td class="time " id="entry{{ $i }}3"></td>
    <td class="time " id="entry{{ $i }}4"></td>

@endif