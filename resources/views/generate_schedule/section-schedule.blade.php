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
            /* margin: auto; */
            text-align: left;
        }
        th, td {
            widows: 100px;
            height: 50px;
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
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
            font-size: 12px;
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
                    <th rowspan="2">#</th>
                    <th rowspan="2">Name</th>
                    
                    @foreach($days as $date)
                        <th colspan="1" >{{ $date }}</th>
                    @endforeach
                    
                    <th rowspan="2">Total Hours</th>
                </tr>
                
                <tr>
                    @foreach ($weeks as $week)
                        <th>{{ $week }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach ($data as $key => $resource)
                        <tr>
                            <td> {{ ++$key }} </td>
                            <td style="text-align: left"> {{ $resource->last_name }}, {{ $resource->first_name }} </td>
                            
                            @foreach($dates as $date)
                            <td>
                                <div class="schedule-container">
                                    
                                    @if ($holiday->where('effectiveDate', $date)->count() > 0)
                                        <span style="font-size: 8px;">HOLIDAY</span>
                                    @else
                                        <span style="margin-bottom: 12px"></span>
                                    @endif
                                
                                    @if ($resource->schedule->where('date_start', $date)->count() > 0)
                                        {{-- @if ($pull_out->where('date', $date)->count() > 0)
                                            <div class="col-1 text-start">
                                                <span class="badge badge-warning">PO</span>
                                            </div>
                                        @endif --}}
                                        
                                
                                        <div class="schedule-cell">
                                            <span class="schedule-time">
                                                {{ date('h A', strtotime(substr($resource->schedule->first()->timeShift->first_in ?? '', 0, 2) . ':00')) }} <br>

                                                @if ($resource->schedule->first()->timeShift->second_out ?? '' != null)
                                                    {{ date('h A', strtotime(substr($resource->schedule->first()->timeShift->second_out ?? '', 0, 2) . ':00')) }} <br>
                                                @else
                                                    {{ date('h A', strtotime(substr($resource->schedule->first()->timeShift->first_out ?? '', 0, 2) . ':00')) }} <br>
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                </div>
                              </td>
                            @endforeach

                            <td> {{ $resource->schedule->first()->timeShift->total_hours ?? '' }} </td>
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
                {{-- {{ $user->name }} --}}
                <span>Position</span>
            </div>

            <div class="text-center">
                <span class="float-start">Approved By:</span>
                <br>
                <span class="signature"></span>
                <br>
                <span>Position</span>
            </div>

            <div class="text-center">
                <span class="float-start">Noted By:</span>
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
