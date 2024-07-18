<style>
    /* @import url('https://fonts.googleapis.com/css2?family=Onest:wght@200&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&display=swap') body {
        display: flex;
        justify-content: center;
        font-family: "Roboto Condensed", sans-serif;
        user-select: none;
    } */

    #po {
        width: 100%;
        padding: 5px;
    }

    .fentry {
        font-size: 13px
    }

    #tabledate {
        width: 100%;
        background-color: #F8F4EA;

        border-collapse: separate;
        text-align: center;
    }

    #tabledate tr th {
        font-size: 11px;
        text-transform: uppercase;
        color: #597E52;

    }

    #tabledate tr td {

        border-top: 1px solid rgb(196, 197, 201);
    }

    #tabledate tr .time {
        font-weight: bold;
        color: #57805e;
        padding: 12px;
    }

    #tabledate tr .timefirstarrival {
        font-weight: normal;
        text-transform: uppercase;
        font-size: 10px;
    }

    #tabledate #tblheader tr td {
        font-weight: normal;
        font-size: 11px;
        color: #637A9F;
    }

    #btnExport {
        margin-bottom: 10px;
        padding: 10px 20px 10px 20px;
        border:1px solid transparent;

        outline: none;
        background-color:#0283ca;
        color: rgb(255, 255, 255);
        cursor: pointer;
        border-radius: 5px;
        transition: all ease-out 0.4s;
    }

    #btnExport:hover {
        background-color:rgb(20, 89, 126);

        border: 1px solid #3468C0;
    }
</style>

<div id="po">

    <button id="btnExport" onclick="window.open('{{url('/').'/api/dtr-generate?biometric_id=['.$biometric_id.']&monthof='.$month.'&yearof='.$year.'&view=0&frontview=0'}}', '_blank')">Export DTR</button>

    <table id="tabledate">

        <tr>
            <th colspan="2" style="background-color: whitesmoke;border-bottom: 1px solid rgb(197, 196, 196);font-size:45px">
                {{ date('F', strtotime($year . '-' . $month . '-1')) }}
            </th>

            <th colspan="2" style="border-bottom: 1px solid rgb(197, 196, 196);font-size:15px">AM</th>

            <th colspan="2" style="border-bottom: 1px solid rgb(197, 196, 196);font-size:15px">PM</th>
            <th>

                <table id="tblheader">
                    <tr>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            </th>
            <th style="background-color: whitesmoke">

            </th>
        </tr>

        <tr>
            <th colspan="2" style="background-color: whitesmoke">
                Day
            </th>

            <th>Arrival</th>
            <th style="border-right: 1px solid rgb(184, 184, 184) ">Departure</th>
            <th>Arrival</th>
            <th>Departure</th>
            <th>
                UNDERTIME
                <table id="tblheader">
                    <tr>
                        <td>Hours</td>
                        <td>Minutes</td>
                    </tr>
                </table>
            </th>
            <th
                style="background-color: whitesmoke; border-right: 1px solid rgb(184, 184, 184);border-left: 1px solid rgb(184, 184, 184)">
                Schedule
            </th>
         <th
            style="width:80px !important;background-color: whitesmoke; border-right: 1px solid rgb(184, 184, 184);">
              Night Shift Hours
          </th>
            <th style="background-color: whitesmoke">
                Remarks
            </th>
        </tr>


        <tbody>
            @php
                $isExcept = false;
            @endphp

            @for ($i = 1; $i <= $daysInMonth; $i++)
                @php
                    // $checkIn = array_filter($dtrRecords->toArray(), function ($res) use ($i) {
                    //     return date('d', strtotime($res->first_in)) == $i &&
                    //         date('d', strtotime($res->first_out)) == $i + 1;
                    // });

                    // $val = 0;
                    // $outdd = array_map(function ($res) {
                    //     return [
                    //         'first_out' => $res['first_out'],
                    //     ];
                    // }, $checkIn);

                    //Check LeaveApplication
                    $filteredleaveDates = [];

                    foreach ($leaveapp as $row) {
                        foreach ($row['dates_covered'] as $date) {
                            $filteredleaveDates[] = strtotime($date);
                        }
                    }
                    $leaveApplication = array_filter($filteredleaveDates, function ($timestamp) use (
                        $year,
                        $month,
                        $i,
                    ) {
                        $dateToCompare = date('Y-m-d', $timestamp);
                        $dateToMatch = date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                        return $dateToCompare === $dateToMatch;
                    });
                    $leave_Count = count($leaveApplication);

                    //Check obD ates
                    $filteredOBDates = [];
                    foreach ($obApp as $row) {
                        foreach ($row['dates_covered'] as $date) {
                            $filteredOBDates[] = strtotime($date);
                        }
                    }
                    $obApplication = array_filter($filteredOBDates, function ($timestamp) use ($year, $month, $i) {
                        $dateToCompare = date('Y-m-d', $timestamp);
                        $dateToMatch = date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                        return $dateToCompare === $dateToMatch;
                    });
                    $ob_Count = count($obApplication);

                    //Check otDates
                    $filteredOTDates = [];
                    foreach ($otApp as $row) {
                        foreach ($row['dates_covered'] as $date) {
                            $filteredOTDates[] = strtotime($date);
                        }
                    }
                    $otApplication = array_filter($filteredOTDates, function ($timestamp) use ($year, $month, $i) {
                        $dateToCompare = date('Y-m-d', $timestamp);
                        $dateToMatch = date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                        return $dateToCompare === $dateToMatch;
                    });
                    $ot_Count = count($otApplication);

                    $ctoApplication = array_filter($ctoApp, function ($row) use ($year, $month, $i) {
                        $dateToCompare = date('Y-m-d', strtotime($row['date']));
                        $dateToMatch = date('Y-m-d', strtotime($year . '-' . $month . '-' . $i));
                        return $dateToCompare === $dateToMatch;
                    });
                    $cto_Count = count($ctoApplication);

                    $ourdata = [];

                    foreach ($leaveapp as $row) {
                        $dates_Interval = [];
                        $from = strtotime($row['from']);
                        $to = strtotime($row['to']);
                        while ($from <= $to) {
                            $dates_Interval[] = date('Y-m-d', $from);
                            $from = strtotime('+1 day', $from);
                        }

                        if (
                            in_array($year . '-' . sprintf('%02d', $month) . '-' . sprintf('%02d', $i), $dates_Interval)
                        ) {
                            // Date is covered, include this leave application in $leavedata
                            $ourdata[] = [
                                'country' => $row['country'],
                                'city' => $row['city'],
                                'from' => $row['from'],
                                'to' => $row['to'],
                                'without_pay' => $row['without_pay'],
                                'leavetype' => $row['leavetype'],
                            ];
                        }
                    }
                    $leavemessage = '';
                    foreach ($ourdata as $key => $value) {
                        $leavemessage = $value['leavetype'];

                    }

                    $officialTime = 'Official Time';
                    $officialBusinessMessage = 'Official Business';
                    $absentMessage = 'Absent';
                    $dayoffmessage = 'Day-Off';
                    $holidayMessage = 'HOLIDAY';
                    $ctoMessage = 'CTO';
                @endphp

                <tr>
                    <td
                        style="color:#3468C0;text-align:center;width:60px;border-right :1px solid rgb(196, 197, 201);background-color: whitesmoke">
                        {{ date('d', strtotime(date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)))) }}

                    </td>
                    <td style="width: 80px;border-right :1px solid rgb(196, 197, 201);background-color: whitesmoke">
                        <span style="color:#637A9F; font-size:12px">
                            {{ date('l', strtotime(date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)))) }}
                        </span>
                    </td>

                    @include('dtr.TableDtrDate')
{{--
                    @if (count($checkIn) >= 1)
                        @php $val = $i; @endphp
                    @endif --}}

                </tr>
            @endfor
        </tbody>
    </table>




</div>

<script>
    document.addEventListener("keydown", function(event) {
        if (event.keyCode === 123) {
            event.preventDefault();
        }
    });

    document.addEventListener("contextmenu", function(e) {
        e.preventDefault();
    });


    document.addEventListener("keydown", function(e) {
        if (e.key === "F12" || (e.ctrlKey && e.shiftKey && (e.key === "I" || e.key === "J"))) {
            e.preventDefault();
        }
    });
</script>
