<style>
    @import url('https://fonts.googleapis.com/css2?family=Onest:wght@200&display=swap');

    body {
        display: flex;
        justify-content: center;
        font-family: 'Onest', sans-serif;
        user-select: none;
    }

    #po {
        width: 395px;
        padding: 5px;
    }

    #titleBar {
        text-align: center;
        font-size: 9px;
        font-weight: 350;
        margin-bottom: 5px;
    }

    #zcmc {
        font-size: 11px;
        font-weight: 450;
    }

    #addr {
        font-size: 8px;
        font-weight: 350;
    }

    #header {
        text-align: center;
        margin-top: -21px;
    }

    #header h6 {
        font-size: 11px;
        letter-spacing: 1px;
    }

    #userName {
        text-align: center;
        text-transform: uppercase;
        margin-top: -20px;
        font-size: 12px;

        font-weight: bold
    }

    #userName div {

        height: 1.5px;
        width: 100%;
        background-color: gray;

    }

    #userName span {
        font-size: 10px;
        font-weight: 520;
        font-weight: bold;
    }

    .ftmo {
        display: flex;
        width: 100%;
        font-weight: normal;
        font-weight: bold
    }

    .ftmo>* {
        flex-grow: 1;
        /* Makes all items expand equally */
        flex-basis: 0;
        /* Distributes available space equally among items */
        max-width: 100%;
        /* Ensure that items don't exceed the container width */
    }

    .ftmo span {
        font-size: 13px;
        text-transform: uppercase;
    }

    #f1 {
        margin-top: 2px;
    }

    #f2 {
        text-align: center !important;
    }

    #f2 div {
        height: 1.5px;
        background-color: gray;
    }

    .tit {
        font-weight: 500;
        font-size: 11px;

    }

    .ot {
        font-size: 10px;
        font-weight: bold
    }

    #zcmclogo {
        width: 35px;
        float: left;
    }

    #dohlogo {
        width: 50px;
        float: right;
    }

    /* Apply styling to the entire table */
    #tabledate {
        width: 98%;
        margin-left: 1%;
        border-collapse: collapse;
        /* Combine adjacent borders into a single border */

    }

    /* Style table rows */
    #tabledate tr {
        font-size: 9px !important;

    }

    /* Style table headers (th) */
    #tabledate th {
        background-color: #f2f2f2;
        /* Background color for header cells */
        font-size: 9px;
        font-weight: 520;
        text-align: center;

        text-transform: uppercase;

        /* Add padding to headers for spacing */

    }

    /* Style table data cells (td) */
    #tabledate td {
        text-align: center;
        border: 1px solid rgb(158, 153, 153);

        /* Add padding to data cells for spacing */
        font-size: 9px !important;
        width: 38px !important;
        height: 21px !important;

        text-transform: uppercase;


    }

    /* Alternate row background color for better readability */
    #tabledate tr:nth-child(even) {
        /* background-color: #e0e0e0; */
    }


    .certification {
        text-align: left;
        margin-top: -10px;

    }

    .certification p {
        font-size: 10px;
        line-height: 1;
    }

    .signature {

        text-align: center;
        margin-top: 2px;

    }

    .signature .line {
        height: 2px;
        background-color: gray;
        width: 60%;

        margin-left: 20%;


    }

    .signature span {
        font-size: 11px
    }

    .footer {
        margin-top: 20px;
    }

    .footer span {
        font-size: 10px;


    }

    #lfooter {
        font-size: 9px;
        width: 100% !important;
    }

    #f1 {
        float: left;
    }

    #f2 {

        text-align: right;
    }

    #f3 {
        text-align: right;
    }

    .fentry {
        color: black;
        font-weight: bold
    }

    #tblheader {
        border-collapse: collapse;
    }

    #tblheader tr td {
        padding: 1px;
        border: 1px solid gray;

        text-transform: capitalize;
    }

    #headertop {
        border-bottom: 1px solid rgb(197, 194, 194);
        border-top: 1px solid rgb(197, 194, 194);
        font-weight: bold
    }

    #headertop th {
        font-size: 11px;
        font-weight: bold;
        color: #656f74
    }
</style>

<div id="po">
    {{--  d:\ciisDTR\dtr\storage\app\public\logo\doh.jpeg d:\ciisDTR\dtr\storage\app\public\logo\zcmc.jpeg resources/views/logo/zcmc.jpeg  --}}
    @if ($print_view)
        <img id="zcmclogo" src="{{ public_path('storage/logo/zcmc.jpeg') }}" alt="zcmcLogo">
        <img id="dohlogo" src="{{ public_path('storage/logo/doh.jpeg') }}" alt="dohLogo">
    @else
        <img id="zcmclogo" src="{{ asset('storage/logo/zcmc.jpeg') }}" alt="zcmcLogo">
        <img id="dohlogo" src="{{ asset('storage/logo/doh.jpeg') }}" alt="dohLogo">
    @endif


    <div id="titleBar">


        <span id="rotp">
            Republic of the Philippines
            <br>
            Department of Health
        </span>
        <br>
        <span id="zcmc">
            ZAMBOANGA CITY MEDICAL CENTER
        </span>
        <br>
        <span id="addr">
            DR. EVANGELISTA ST., STA. CATALINA, ZAMBOANGA CITY
        </span>

    </div>



    <div id="header">
        <h6>DAILY TIME RECORD</h6>
    </div>

    <div id="userName">
        {{ $Employee_Name }}
        @if (isset($data))
            <hr>
        @else
            <div></div>
        @endif
        {{-- <span>NAME</span> --}}
    </div>


    <table style="width:100% !important;">


        <tr>
            <td class="tit">
                <span>
                    For the month of
                </span>
            </td>
            <td class="ot">
                : <span>{{ date('F', strtotime($year . '-' . $month . '-1')) }} 1 to {{ $daysInMonth }}
                    {{ $year }}</span>/<span style="font-size:10px">Regular Days</span>
            </td>
        </tr>
        {{-- @if (count($dtrRecords) >= 1) --}}


        @if (strlen($Arrival_Departure) <= 35)
            <tr>
                <td class="tit">
                    <span>
                        Official hours for
                    </span>
                </td>
                <td class="ot">
                    : {{ $OHF }}
                </td>
            </tr>
        @endif

        <tr>
            <td class="tit">
                <span>
                    Arrival and Departure
                </span>
            </td>
            {{-- {{ substr($Arrival_Departure, 0, 35) }} --}}
            <td class="ot" style="max-width: 50px">
                : {{ $Arrival_Departure }}
            </td>
        </tr>

        {{-- @endif --}}

    </table>




    <table id="tabledate">
        <tr id="headertop">
            <th colspan="2">

            </th>
            <th colspan="2" style="text-align: center">
                AM
            </th>
            <th colspan="2" style="text-align: center">PM</th>
            <th style="text-align: center" colspan="2">UNDERTIME</th>
        </tr>

        <tr style="padding: 5px">
            <th>

            </th>
            <th>

            </th>
            <th>Arrival</th>
            <th>Departure</th>
            <th>Arrival</th>
            <th>Departure</th>
            <th>
                Hours

            </th>
            <th>
                Minutes
            </th>
        </tr>

        {{-- {{print_r($dtrRecords)}} --}}
        <tbody>
            @php
                $isExcept = false;
            @endphp
            @for ($i = 1; $i <= $daysInMonth; $i++)
                @php

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

                    $officialTime = 'Official Time';
                    $officialBusinessMessage = 'OFf|Business';
                    $absentMessage = 'Absent';
                    $dayoffmessage = 'Day-Off';
                    $holidayMessage = 'HOLIDAY';
                    $ctoMessage = 'CTO';

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

                        // }
                    }

                    //    $leavemessage = 'On leave';

                @endphp

                <tr>

                    <td style="width: 35px !important;font-size:10px;font-weight:bold">{{ $i }}</td>
                    <td
                        style="font-weight:bold;text-transform: capitalize; color:#010b0f; font-size:10px;width: 35px !important;font-size:10px">
                        {{ date('D', strtotime(date('Y-m-d', strtotime($year . '-' . $month . '-' . $i)))) }}
                    </td>

                    @include('generate_dtr.TableDtrDate')
                </tr>
            @endfor
        </tbody>
    </table>
    <div class="certification" style="padding: 2px">
        <p>I certify on my honor that the above is a true and correct report of the hours of work performed, recorded
            daily at the time of arrival and departure from the office.</p>
    </div>
    <br>
    <div class="signature">
        <div style="font-size: 12px;text-transform:uppercase;font-weight:bold">
            {{-- {{ $Employee_Name }} --}}
        </div>
        <div class="line"></div>
        <span> Verified as to prescribed hours</span>
    </div>
    <br>
    <div class="signature">
        <div style="font-size: 12px;text-transform:uppercase;font-weight:bold">
            {{ $Incharge }}
        </div>
        <div class="line"></div>
        <span> In Charge</span>
    </div>
    <div class="footer" style="padding: 2px">
        <span>Adopted from CSC FORM NO. 48</span>
        <br>
        <table id="lfooter">
            <tr>
                <td id="f1">ZCMC-F-HRMO-01</td>
                <td id="f2">ReV.0</td>
                <td id="f3">Effectivity Date: June 2, 2014</td>
            </tr>
        </table>

    </div>

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
