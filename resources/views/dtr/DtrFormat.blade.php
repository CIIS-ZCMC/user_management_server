@php
    // Pre-calculate and cache dates and formats to avoid repetitive calculations in the loop
    $monthName = date('F', strtotime($year . '-' . $month . '-1'));
    $dateCache = [];
    $dayNames = [];
    $dateObjects = [];
    $dayNumbers = [];
    
    // Pre-process leave applications for faster lookups
    $leaveCache = [];
    $obCache = [];
    $otCache = [];
    $ctoCache = [];
    
    // Create date strings for faster comparisons
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $dateStr = $year . '-' . sprintf('%02d', $month) . '-' . sprintf('%02d', $i);
        $timestamp = strtotime($dateStr);
        $dateCache[$i] = date('Y-m-d', $timestamp);
        $dayNames[$i] = date('l', $timestamp);
        $dayNumbers[$i] = date('d', $timestamp);
        $dateObjects[$i] = $timestamp;
        
        // Initialize caches for this date
        $leaveCache[$dateStr] = false;
        $obCache[$dateStr] = false;
        $otCache[$dateStr] = false;
        $ctoCache[$dateStr] = false;
    }
    
    // Process leave applications once
    foreach ($leaveapp as $row) {
        foreach ($row['dates_covered'] as $date) {
            $dateKey = date('Y-m-d', strtotime($date));
            $dateParts = explode('-', $dateKey);
            if ($dateParts[0] == $year && $dateParts[1] == sprintf('%02d', $month)) {
                $leaveCache[$dateKey] = [
                    'leavetype' => $row['leavetype'],
                    'without_pay' => $row['without_pay']
                ];
            }
        }
    }
    
    // Process OB applications once
    foreach ($obApp as $row) {
        foreach ($row['dates_covered'] as $date) {
            $dateKey = date('Y-m-d', strtotime($date));
            $dateParts = explode('-', $dateKey);
            if ($dateParts[0] == $year && $dateParts[1] == sprintf('%02d', $month)) {
                $obCache[$dateKey] = true;
            }
        }
    }
    
    // Process OT applications once
    foreach ($otApp as $row) {
        foreach ($row['dates_covered'] as $date) {
            $dateKey = date('Y-m-d', strtotime($date));
            $dateParts = explode('-', $dateKey);
            if ($dateParts[0] == $year && $dateParts[1] == sprintf('%02d', $month)) {
                $otCache[$dateKey] = true;
            }
        }
    }
    
    // Process CTO applications once
    foreach ($ctoApp as $row) {
        $dateKey = date('Y-m-d', strtotime($row['date']));
        $dateParts = explode('-', $dateKey);
        if ($dateParts[0] == $year && $dateParts[1] == sprintf('%02d', $month)) {
            $ctoCache[$dateKey] = true;
        }
    }
    
    // Define constants
    $officialTime = 'Official Time';
    $officialBusinessMessage = 'Official Business';
    $absentMessage = 'Absent';
    $dayoffmessage = 'Day-Off';
    $holidayMessage = 'HOLIDAY';
    $ctoMessage = 'CTO';
@endphp

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
        border: 1px solid transparent;
        outline: none;
        background-color: #0283ca;
        color: rgb(255, 255, 255);
        cursor: pointer;
        border-radius: 5px;
        transition: all ease-out 0.4s;
    }

    #btnExport:hover {
        background-color: rgb(20, 89, 126);
        border: 1px solid #3468C0;
    }
</style>

<div id="po">

    {{-- <button id="btnExport"
        onclick="window.open('{{ url('/') . '/api/dtr-generate?biometric_id=[' . $biometric_id . ']&monthof=' . $month . '&yearof=' . $year . '&view=0&frontview=0' }}', '_blank')">Export
        DTR</button> --}}


    <table id="tabledate">

        <tr>
            <th colspan="2"
                style="background-color: whitesmoke;border-bottom: 1px solid rgb(197, 196, 196);font-size:45px">
                {{ $monthName }}
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
            <th style="width:80px !important;background-color: whitesmoke; border-right: 1px solid rgb(184, 184, 184);">
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
                    $currentDate = $dateCache[$i];
                    
                    // Get leave status from cache
                    $leave_Count = $leaveCache[$currentDate] ? 1 : 0;
                    $ob_Count = $obCache[$currentDate] ? 1 : 0;
                    $ot_Count = $otCache[$currentDate] ? 1 : 0;
                    $cto_Count = $ctoCache[$currentDate] ? 1 : 0;
                    
                    // Get leave message directly instead of recalculating
                    $leavemessage = '';
                    if ($leaveCache[$currentDate]) {
                        $leavemessage = $leaveCache[$currentDate]['leavetype'];
                    }
                @endphp

                <tr>
                    <td
                        style="color:#3468C0;text-align:center;width:60px;border-right:1px solid rgb(196, 197, 201);background-color: whitesmoke">
                        {{ $dayNumbers[$i] }}
                    </td>
                    <td style="width: 80px;border-right:1px solid rgb(196, 197, 201);background-color: whitesmoke">
                        <span style="color:#637A9F; font-size:12px">
                            {{ $dayNames[$i] }}
                        </span>
                    </td>

                    @include('dtr.TableDtrDate')
                </tr>
            @endfor
        </tbody>
    </table>




</div>

<script>
    document.addEventListener("keydown", function(event) {
        if (event.keyCode === 123 || event.key === "F12" || (event.ctrlKey && event.shiftKey && (event.key === "I" || event.key === "J"))) {
            event.preventDefault();
        }
    });

    document.addEventListener("contextmenu", function(e) {
        e.preventDefault();
    });
</script>
