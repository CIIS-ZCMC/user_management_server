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
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        header {
            padding: 10px;
            text-align: center;
        }
        header img {
            max-height: 50px;
            margin-right: 10px;
        }
        main {
            max-width: 800px;
            margin: 20px auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: auto;
            text-align: left;
        }
        th, td {
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
            margin-top: 20px;
        }
    </style>
</head>
<body>
    {{-- @php
        $month  = $request->month;   // Replace with the desired month (1 to 12)
        $year   = $request->year;     // Replace with the desired year
        $days   = app('Helpers')->getDatesInMonth($year, $month, "Day");
        $weeks  = app('Helpers')->getDatesInMonth($year, $month, "Week");
        $dates  = app('Helpers')->getDatesInMonth($year, $month, "");
    @endphp --}}

    <header>
        <img src="{{ asset('storage/zcmc.png') }}" alt="Logo Left">

        <h6>Republic of the Philippines</h6>
        <h3 style="margin: 0">  ZAMBOANGA CITY MEDICAL CENTER </h3>
        <h6>Dr. Evangelista Street, Sta. Catalina, Zamboanga City</h6>

        <img src="{{ asset('storage/doh.png') }}" alt="Logo Right">
    
        <!-- Add any other header content as needed -->
    </header>

    <div class="container-fluid">

        <div class="row" style="margin-bottom: 10px">
            <div class="col-6 text-start">
            Department : 
            </div>
            
            <div class="col-6 text-end">
            For The Month of :  {{ $month }}
            </div>
            
            <div class="col">
            Section :
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
                                <td style="font-weight: bold; font-size: 12px;">
                                    @if ($resource->schedule->where('date_start', $date)->count() > 0)
                                        {{ date('h A', strtotime(substr($resource->schedule->first()->timeShift->first_in ?? '' , 0, 2) . ':00')) }} <br>

                                        @if ($resource->schedule->first()->timeShift->second_out ?? '' != null)
                                            {{ date('h A', strtotime(substr($resource->schedule->first()->timeShift->second_out ?? '' , 0, 2) . ':00')) }}
                                        @else
                                            {{ date('h A', strtotime(substr($resource->schedule->first()->timeShift->first_out ?? '' , 0, 2) . ':00')) }}
                                        @endif
                                    @endif
                                </td>
                            @endforeach

                            <td> {{ $resource->schedule->first()->timeShift->total_hours ?? '' }} </td>
                        </tr>
                @endforeach

                {{-- @foreach ($data as $key => $resource)
                    @foreach ($resource->employee as $employee)
                    <tr>
                        <td> {{ ++$key }} </td>
                        <td style="text-align: left"> {{ $employee->last_name }}, {{ $employee->first_name }} </td>

                        @foreach($dates as $date)
                            <td olspan="1" style="font-size: 12px">
                                @if($resource->date_start = $date)
                                    {{ date('h A', strtotime(substr($resource->timeShift->first_in, 0, 2) . ':00')) }} <br>

                                    @if ($resource->timeShift->second_out != null)
                                        {{ date('h A', strtotime(substr($resource->timeShift->second_out, 0, 2) . ':00')) }}
                                    @else
                                        {{ date('h A', strtotime(substr($resource->timeShift->first_out, 0, 2) . ':00')) }}
                                    @endif
                                @endif
                            </td>
                        @endforeach

                        <td> {{ $resource->timeShift->total_hours }} </td>
                    </tr>
                    @endforeach
                @endforeach --}}
            </tbody>
        </table>
    </div>

    <footer>
        <p style="margin-bottom: 0px;"><span class="text-danger">*</span> Note: </p>
        <span style="padding-left: 10px">Station/Department Contact No: </span>

        <div class="signatures">
            <div class="signature">
                <p>Signature 1</p>
                <!-- Add space for signature -->
            </div>
            <div class="signature">
                <p>Signature 2</p>
                <!-- Add space for signature -->
            </div>
            <div class="signature">
                <p>Signature 3</p>
                <!-- Add space for signature -->
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>

