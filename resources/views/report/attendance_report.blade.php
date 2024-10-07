<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6oIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <style>
        * {
            font-family: Arial, Helvetica, sans-serif;
            box-sizing: border-box;
            padding: 0;
            margin: 10px;
        }

        header {
            width: 90%;
            text-align: center;
            display: table;
            margin: auto;
        }

        .header-container {
            display: table-row;
        }

        .header-item {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        #zcmclogo, #dohlogo {
            height: 65px;
        }

        .header-text {
            text-align: center;
        }

        .header-text h5 {
            margin: 0;
        }

        .logo-container {
            width: 20%;
        }

        .text-container {
            width: 60%;
        }

        /* Horizontal Divider */
        .divider {
            width: 80%;
            border-top: 1px solid rgb(212, 212, 212);
            margin: 25px 10%;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 14px;
        }

        th,
        td {
            border: 1px solid #696969;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #e4e4e4;
        }

        /* Footer */
        @page {
            margin: 100px 50px;
        }

        footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
            font-size: 12px;
        }

        td.header-name {
            width: 'auto'
        }
        .page-number:before {
            content: "Page " counter(page);
        }

        /* FILTER SUMARRY */
        .filter-summary-container {
            margin-left: 0px;
            /* font-size: 14px !important; */
            /* border: 1px solid rgb(173, 173, 173); */
            width: 100%;
            /* text-align: right */
        }
    </style>
</head>
<body>
<header>
    <div class="header-container">
        <div class="header-item logo-container">
            <img id="zcmclogo" src="{{ base_path() . '/public/storage/logo/zcmc.jpeg' }}" alt="ZCMC Logo">
        </div>
        <div class="header-item text-container header-text">
            <span>Republic of the Philippines</span>
            <h5>ZAMBOANGA CITY MEDICAL CENTER</h5>
            <span>Dr. Evangelista Street, Sta. Catalina, Zamboanga City</span>
        </div>
        <div class="header-item logo-container">
            <img id="dohlogo" src="{{ base_path() . '/public/storage/logo/doh.jpeg' }}" alt="DOH Logo">
        </div>
    </div>
</header>

<!-- Horizontal Divider -->
<div class="divider"></div>

@php
    use Carbon\Carbon;

    // Get the current date and time
    $now = Carbon::now();
    // Format the date in a human-readable format
    $formattedDate = $now->format('F j, Y');
@endphp

<div style="text-align:center; ">
    <h3 style="margin: 0; text-transform: capitalize">
        Employee {{ $filters['report_type'] }}  
        @if ($filters['report_type'] === 'perfect')
        Attendance
        @endif 
        Report
    </h3>

    <p style="font-size: 14px">as of {{ $formattedDate }}</p>
</div>

    @if(isset($filters))
      
        {{-- <hr style="margin: 10px 0px "> --}}
        <div class="filter-summary-container">  
            {{-- <h2 style="margin-bottom: 15px">Filter Summary</h2> --}}
            <p>Total Employee(s):<b>{{ COUNT($rows) ?? '0' }}</b></p>

            {{-- FOR DATE PERIOD --}}
            @if(isset($filters['month_of']))
                @php
                    $date = \DateTime::createFromFormat('!m Y', sprintf('%02d %d', $filters['month_of'], $filters['year_of']));
                @endphp
                <p>Month and Year:<b>{{$date->format('F Y');}}</b></p>
            @endif

            {{-- FOR DATE RANGE --}}
            @if(isset($filters['start_date']))
                @php
                    $date = Carbon::parse($dateString)
                @endphp
                <p>Date range: <b>{{Carbon::parse($filters['start_date'])}} - {{ Carbon::parse($filters['end_date']) }}</b></p>
            @endif

            @if(isset($filters['start_date']))
                <p>Date range: <b>{{Carbon::parse($filters['start_date'])}} - {{ Carbon::parse($filters['end_date']) }}</b></p>
            @endif


            {{-- AREA --}}
            @if(isset($filters['area_name']))
               <p>Area:<b>{{ $filters['area_name'] }}</b></p>
            @endif

            {{-- DESIGNATION --}}
            @if(isset($filters['designation_name']))
                <p>Designation:<b>{{ $filters['designation_name'] ?? '-' }}</b></p>
            @endif

            {{-- SCHEDULE --}}
            @if(isset($filters['first_half']))
                @php
                    $firstHalf = $filters['first_half'];
                    $secondHalf = $filters['second_half'];
                    $monthOf = $filters['month_of'];
                    $yearOf = $filters['year_of'];

                    if ($firstHalf == 1) {
                        $dateRange = '1-15';
                    } else if ($secondHalf == 1) {
                        // Calculate the last day of the month
                        $lastDayOfMonth = \Carbon\Carbon::create($yearOf, $monthOf)->endOfMonth()->day;
                        $dateRange = '16-' . $lastDayOfMonth;
                    } else {
                        $dateRange = "Whole month";
                    } 
                @endphp

                @if(isset($firstHalf) && isset($secondHalf) )
                <p>Range: <b>{{ $dateRange }}</b></p>
                @endif

            @endif

           
        </div>
    @endif


<hr style="margin: 20px 0px ">
<table class="result-table">
    <tr>
        <th>#</th> <!-- Row number column -->
        @foreach ($columns as $column)
            <th>{{ $column['headerName'] }}</th>
        @endforeach
    </tr>

    @if (!$rows)
        <tr>
            <td colspan="{{ count($columns) + 1 }}" style="text-align: center;">
                No attendance records found
            </td>
        </tr>
    @else
        @foreach ($rows as $row)
            <tr>
                <td>{{ $loop->iteration }}</td> <!-- Display the row number -->
                @foreach ($columns as $column)
                    <td>{{ $row[$column['field']] ?? 'N/A' }}</td>
                @endforeach
            </tr>
        @endforeach
    @endif
</table>

<!-- Footer -->
<footer>
    <div class="page-number"></div>
</footer>
</body>
</html>
