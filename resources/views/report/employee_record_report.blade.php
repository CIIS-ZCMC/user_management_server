<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6oIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <style>

        * {
            font-family: Arial, Helvetica, sans-serif
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

        .page-number:before {
            content: "Page " counter(page);
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
     <h3 style="margin: 0">{{ $report_name }}</h3>
    <p style="font-size: 14px">as of {{ $formattedDate }}</p>
</div>

<table cellspacing="0" cellpadding="0">
    <tr>
        @foreach ($columns as $column)
            <th>
                {{ $column['headerName'] }}
            </th>
        @endforeach
    </tr>

    @if(!$rows)
        <tr>
            <td colspan="{{ count($columns) }}" style="text-align: center;">
                No records found
            </td>
        </tr>
    @else
        @foreach ($rows as $row)
            <tr>
                @foreach ($columns as $column)
                    <td>
                        @php
                            $value = $row[$column['field']] ?? 'N/A';
                            // Convert array values to JSON string for display
                            if (is_array($value)) {
                                $value = json_encode($value);
                            }
                        @endphp
                        {{ $value }}
                    </td>
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
