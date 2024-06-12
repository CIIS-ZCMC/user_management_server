<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 14in;
            height: 8.5in;
        }

        img {
            padding: 5px;
            width: 55px;
        }

        header {
            top: 0%;
            width: 90%;
            text-align: center;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .topnav {
            margin: 0;
            margin-top: 2%;
            height: 30px;
            width: 90%;
            overflow: hidden;
            padding-top: 5px;
        }

        footer {
            padding: 10px;
            max-width: 90%;
            margin-top: 10px;
            text-align: left;
        }


        header .float-left {
            position: absolute;
            top: 0px;
            left: 27%;
        }

        header .float-right {
            position: absolute;
            top: 5px;
            right: 29%;
        }

        .topnav .float-left {
            padding: 0;
            margin: 0;
            float: left;
            display: block;
            text-align: start;
            text-decoration: none;
        }

        .topnav .float-right {
            padding: 0;
            margin: 0;
            float: right;
            display: block;
            text-align: end;
            text-decoration: none;
        }

        .container {
            margin: 0;
            padding: 0;
            max-width: 90%;
            /* overflow-x: auto; */
        }

        table {
            width: 100%;
            /* Adjusted to fill the container */
            /* border-collapse: collapse; */
            /* margin: 0;
            padding-top: 20px;
            text-align: left; */
        }

        table,
        th,
        td {
            border: 1px solid;
        }

        thead,
        th {
            widows: 100px;
            height: 10px;
            border: 1px solid;
            text-align: center;
            font-size: 12px;
            padding: 2px 1px 2px 1px;
        }

        .th-name {
            padding-right: 80px;
            font-size: 12px;
            /* Adjusted font size */
        }

        .td-name {
            font-size: 12px;
            /* Adjusted font size */
            font-weight: bold;
            text-align: left;
        }

        td {
            text-align: center;
            font-size: 12px;
            /* Adjusted font size */
        }

        .signatures {
            padding: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .signature {
            margin-top: 10px;
            padding-top: 10px;
            border-bottom: 1px solid #000;
            display: inline-block;
            align-items: center;
            text-align: center;
            width: 250px;
        }

        .row {
            display: grid;
            grid-template-columns: auto auto auto auto;
            grid-gap: 10px;
            padding: 5px;
            width: 90%;
            align-items: center;
        }

        .row .row-item {
            display: inline-block;
            grid-row: 1 / span 5;
            text-align: center;
            width: 200px;
            padding-left: 8rem;
            padding-top: 10px;
            margin-top: 5px;
        }

        .underline {
            padding: 0;
            margin: 0;
            border-bottom: 1px solid #000;
            /* display: inline-block; */
        }

        .schedule-cell {
            font-size: 10px;
        }

        @media print {
            @page {
                size: landscape;
            }

            body {
                font-size: 12pt;
            }

            .container {
                margin: 20px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="float-left">
            <img style="height: 65px;" id="zcmclogo" src="{{ base_path() . '\public\storage\logo/zcmc.jpeg' }}"
                alt="ZCMC Logo">
        </div>

        <div>
            <span>Republic of the Philippines</span>
            <h6 style="margin: 0;">ZAMBOANGA CITY MEDICAL CENTER</h6>
            <span>Dr. Evangelista Street, Sta. Catalina, Zamboanga City</span>
        </div>

        <div class="float-right">
            <img style="width: 62px" id="dohlogo" src="{{ base_path() . '\public\storage\logo/doh.jpeg' }}"
                alt="DOH Logo">
        </div>
    </header>

    <div class="topnav">
        <div class="float-left">
            Department : <span class="underline">{{ $user->assignedArea->findDetails()['details']['name'] }}</span>
        </div>

        <div class="float-right">
            For The Month of : <span class="underline"> {{ date('F Y', strtotime($month)) }} </span>
        </div>
    </div>

    <div class="container">
        <div class="table-responsive"> <!-- Added -->
            <table class="table-bordered" border="1" cellspacing="0" cellpadding="8">
                <thead>
                    <tr>
                        <th rowspan="2">#</th>
                        <th class="th-name" rowspan="2">Name</th>

                        @foreach ($dates as $date)
                            <th colspan="1">{{ \Carbon\Carbon::parse($date)->format('d') }}</th>
                        @endforeach

                        <th rowspan="2" style="width: 10px; font-size: 10px">Total Hours</th>
                    </tr>

                    <tr>
                        @foreach ($dates as $date)
                            <th style="font-size: 10px">{{ \Carbon\Carbon::parse($date)->format('D') }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($employee as $key => $data)
                        <tr>
                            <td> {{ ++$key }} </td>
                            <td class="td-name"> {{ $data->personalInformation->name() }} </td>

                            @php
                                $totalHours = 0;
                            @endphp

                            @foreach ($dates as $date)
                                <td>
                                    <div class="schedule-container">
                                        {{-- @if ($holiday->where('month_day', date('m-d', strtotime($date)))->count() > 0)
                                            <span class="schedule-cell">H</span>
                                        @else --}}
                                        @php
                                            $isHoliday =
                                                $holiday->where('month_day', date('m-d', strtotime($date)))->count() >
                                                0;
                                            $foundShift = false;
                                        @endphp

                                        @if ($isHoliday)
                                            @foreach ($data->schedule as $shift)
                                                @if ($shift['date'] === $date)
                                                    <span class="schedule-cell">{!! $shift->timeShift->calendarTimeShiftDetails() !!}</span>
                                                    @php
                                                        $totalHours += $shift->timeShift->total_hours;
                                                        $foundShift = true;
                                                    @endphp
                                                @endif
                                            @endforeach

                                            @if (!$foundShift)
                                                <span class="schedule-cell">H</span>
                                            @endif
                                        @else
                                            {{-- Assuming $data->schedule is an array --}}
                                            @foreach ($data->schedule as $shift)
                                                @if ($shift['date'] === $date)
                                                    <span class="schedule-cell">{!! $shift->timeShift->calendarTimeShiftDetails() !!}</span>
                                                    @php
                                                        $totalHours += $shift->timeShift->total_hours;
                                                        $foundShift = true;
                                                    @endphp
                                                @endif
                                            @endforeach

                                            {{-- If no shift found for the date --}}
                                            @if (!$foundShift)
                                                <span class="schedule-cell">x</span>
                                            @endif
                                        @endif
                                        {{-- @endif --}}
                                    </div>
                                </td>
                            @endforeach

                            <td> {{ $totalHours }} </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        <div class="signatures">
            <div class="row">
                <div class="row-item">
                    <span> Prepared By </span>
                    <span class="signature">{{ $user->personalInformation->employeeName() }}</span>
                    <span style="font-size: 12px">{{ $user->findDesignation()['name'] ?? 'Scheduler' }}</span>
                </div>

                <div class="row-item">
                    <span> Reviewed By </span>
                    @if ($recommending_officer === null)
                        <span class="signature"></span>
                        <span style="margin-top: 100px"></span>
                    @else
                        <span class="signature">{{ $recommending_officer->personalInformation->employeeName() }}</span>
                        <span
                            style="font-size: 12px">{{ $recommending_officer->findDesignation()['name'] ?? null }}</span>
                    @endif
                </div>

                <div class="row-item">
                    <span> Approved By </span>
                    @if ($recommending_officer === null)
                        <span class="signature"></span>
                        <span style="margin-top: 100px"></span>
                    @else
                        <span class="signature">{{ $approving_officer->personalInformation->employeeName() }}</span>
                        <span
                            style="font-size: 12px">{{ $approving_officer->findDesignation()['name'] ?? null }}</span>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
    </script>
</body>

</html>
