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

        #zcmclogo,
        #dohlogo {
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
            font-size: 13px;
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

        .td-summary {
            width: 40%; 
            border:none
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

    <!-- Report Title and Date -->
    <div style="text-align:center;">
        <h3 style="margin: 0;">{{ $report_name }}</h3>
        <p style="font-size: 14px;">as of {{ $formattedDate }}</p>
    </div>

    <!-- Display the report summary if available -->

    <table>
        <td class="td-summary">     
            <h4>Report Summary</h4>
            <table>
                <thead>
                    <tr>
                        <th>Summary Item</th>
                        <th>Count</th>
                        <th>Summary Item</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total Applications</td>
                        <td>{{ $report_summary['total_applications'] ?? '0' }}</td>
                        <td>Total Approved</td>
                        <td>{{ $report_summary['total_approved'] ?? '0' }}</td>
                    </tr>
                    <tr>
                        <td>Total Applied</td>
                        <td>{{ $report_summary['total_applied'] ?? '0' }}</td>
                        <td>Total Cancelled</td>
                        <td>{{ $report_summary['total_cancelled'] ?? '0' }}</td>
                    </tr>
                    <tr>
                        <td>Total Without Pay</td>
                        <td>{{ $report_summary['total_without_pay'] ?? '0' }}</td>
                        <td>Total Received</td>
                        <td>{{ $report_summary['total_received'] ?? '0' }}</td>
                    </tr>
                    <tr>
                        <td>Total With Pay</td>
                        <td>{{ $report_summary['total_with_pay'] ?? '0' }}</td>
                        
                        <td></td>
                        <td></td>
                        <!-- Add more summary fields if needed -->
                    </tr>
                </tbody>
            </table>
        </td>
       

        <td class="td-summary">
            <h4>Leave Type Breakdown</h4>
            @if (!empty($report_summary['leave_type_counts']))
                <table>
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Code</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report_summary['leave_type_counts'] as $leaveType)
                            @if ($leaveType['count'] > 0)
                                <!-- Only show leave types with count greater than 0 -->
                                <tr>
                                    <td>{{ $leaveType['name'] }}</td>
                                    <td>{{ $leaveType['code'] }}</td>
                                    <td>{{ $leaveType['count'] }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>No leave types available.</p>
            @endif
        </td>
        
    </table>


    <hr style="margin: 10px 0px 10px 0px">

    <!-- Table with data -->
    <table>
        <thead>
            <tr>
                <th>#</th> <!-- Row number column -->
                @foreach ($columns as $column)
                    <th>{{ $column['headerName'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if (empty($rows))
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" style="text-align: center;">
                        No records found
                    </td>
                </tr>
            @else
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td> <!-- Display the row number -->
                        @foreach ($columns as $column)
                            <td>
                                @if (is_array($row[$column['field']]))
                                    {{ json_encode($row[$column['field']]) }} <!-- Convert array to JSON string -->
                                @else
                                    {{ $row[$column['field']] ?? 'N/A' }} <!-- Output the string or 'N/A' -->
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endif
        </tbody>

    </table>

    <!-- Footer -->
    <footer>
        <div class="page-number"></div>
    </footer>
</body>

</html>
