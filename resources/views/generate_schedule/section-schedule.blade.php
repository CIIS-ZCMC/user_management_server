<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 14in;
            height: 8.5in;
        }
        
        header {
            padding: 10px;
            text-align: center;
            display: flex; 
            align-items: center; 
            justify-content: space-between;
        }
        
        .container {
            max-width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            text-align: left;
        }
        th {
            widows: 100px;
            height: 10px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
            padding: 2px 1px 2px 1px ;
        }

        .th-name {
            padding-right: 80px
        }

        .td-name {
            font-size: 10px;
            font-weight: bold;
            text-align: left
        }

        td {
            text-align: center;
            font-size: 12px
        }

        footer {
            padding: 10px;
            margin-top: 50px;
            text-align: left;
        }

        .signatures {
            padding: 10px;
            display: flex;
            justify-content: space-between;
        }

        .signature {
            padding-top: 40px;
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 350px;
        }

        .underline {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 100px;
        }

        /* Badge container styles */
        .badge {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }

        .badge-dark {
            background-color: #343a40;
            color: #fff;
        }

        .badge-warning {
            background-color: #f39c12;
            color: #fff;
        }

        .schedule-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .schedule-cell {
            font-weight: bold;
            font-size: 10px;
        }

        .schedule-time {
            vertical-align: center;
        }

        .schedule-time + .schedule-time {
            margin-top: 5px; /* Add some spacing between schedule times */
        }

        @media print {
            @page {
                size: landscape;
            }

            body {
                font-size: 10pt;
            }

            .container {
                margin: 20px;
            }
        }
    </style>
</head>
<body>

    
    <header style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <img src="{{ asset('storage/zcmc.png') }}" alt="Logo Left">
        </div>

        <div style="text-align: center;">

            <span>Republic of the Philippines</h6>
            <h6 style="margin: 0">  ZAMBOANGA CITY MEDICAL CENTER </h3>
            <span>Dr. Evangelista Street, Sta. Catalina, Zamboanga City</h6>
        </div>

        <div>
            <img src="{{ asset('storage/doh.png') }}" alt="Logo Right">
        </div>
    </header>

    <div class="container">
        <div class="row" style="margin-bottom: 10px">
            <div class="col-6">
                <span class="float-start">Department :
                    <span class="underline">  </span>
                </span>
            </div>
            
            <div class="col-6 ">
                <span class="float-end">For The Month of :
                    <span class="underline"> {{ $month }} </span>
                </span>
            </div>
            
            <div class="col">
          
                <span class="float-start">Section :
                    <span class="underline">  </span>
                </span>
            </div>
        </div>

        <table class="table-bordered" border="1" cellspacing="0" cellpadding="10">
            <thead>
                <tr>
                    <th class="schedule-cell" rowspan="2">#</th>
                    <th class="th-name" rowspan="2">Name</th>
                    
                    @foreach($days as $date)
                        <th colspan="1" >{{ $date }}</th>
                    @endforeach
                    
                    <th rowspan="2" style="width: 10px; font-size: 10px">Total Hours</th>
                </tr>
                
                <tr>
                    @foreach ($weeks as $week)
                        <th style="font-size: 10px">{{ $week }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach ($data as $key => $resource)
                        <tr>
                            <td class="schedule-cell"> {{ ++$key }} </td>
                            <td class="td-name"> {{ $resource->personalInformation->name() }}  </td>

                            @php
                                $totalHours = 0;
                            @endphp

                            @foreach($dates as $date)
                            <td>
                                <div class="schedule-container">
                                    @if ($holiday->where('month_day', date('m-d', strtotime($date)))->count() > 0)
                                        <span class="schedule-cell">H</span>
                                    @else
                                        @if ($resource->schedule->where('date', $date)->count() > 0)

                                        @php
                                            $shift = $resource->schedule->first()->timeShift;
                                            $firstIn = strtotime($shift->first_in ?? '');
                                            $secondOut = strtotime($shift->second_out ?? '');
                                            $firstOut = strtotime($shift->first_out ?? '');
            
                                            $totalHours += ($secondOut != null) ? ($secondOut - $firstIn) / 3600 : ($firstOut - $firstIn) / 3600;
                                        @endphp

                                            <span class="schedule-cell">
                                                {{ date('h', $firstIn) }} <br>

                                                @if ($secondOut != null)
                                                    {{ date('h', $secondOut) }} <br>
                                                @else
                                                    {{ date('h', $firstOut) }} <br>
                                                @endif
                                            </span>
                                        @else
                                            <span class="schedule-cell"> x </span>
                                        @endif
                                    @endif
                                </div>
                              </td>
                            @endforeach

                            <td> {{ $totalHours }} </td>
                        </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <footer>
        <p style="margin-bottom: 0px;"><span class="text-danger">*</span> Note: </p>
        <span style="padding-left: 10px">Station/Department Contact No: </span>

        <div class="signatures">
            <div class="text-center">
                <span class="float-start">Prepared By:</span>
                <br>
                <span class="signature"></span>
                <br>
                <span>Position</span>
            </div>

            <div class="text-center">
                {{-- <span class="float-start">Approved By:</span>
                <br>
                <span class="signature"></span>
                <br>
                <span>Position</span> --}}
            </div>

            <div class="text-center">
                <span class="float-start">Approved By:</span>
                <br>
                <span class="signature"></span>
                <br>
                <span>Position</span>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
