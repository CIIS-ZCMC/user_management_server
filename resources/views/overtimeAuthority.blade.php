<!DOCTYPE html>
<html lang="en">
 <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Schedule</title>

 <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        header {

            text-align: center;
        }
        header img {
            max-height: 50px;
            margin-right: 10px;
        }
        main {
            /* max-width: 800px;
            margin: 20px auto; */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: auto;
            text-align: left;
        }
        th, td {
            border: 1px solid #ddd;
            /* padding: 4px; */


        }
        tr, td {
            border: 1px solid #000000;
            padding: 1px;
            font-size:12px

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

        .cell-height-1 {
    padding: 2px !important;
}

.cell-height-2 {
    height: 10px; /* Adjust the height value as needed */
}

#titleBar {
        text-align: center;
        font-size: 10px;
        font-weight: 350;
        margin-bottom: 5px;
    }

    #zcmc {
        font-size: 14px;
        font-weight: 450;
    }

    #addr {
        font-size: 10px;
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

    #zcmclogo {
        width: 55px;
        float: left;
        margin-left:10%
    }

    #dohlogo {
        width: 70px;
        float: right;
        margin-right:10%
    }
    #rotp{
        font-size:12px;
    }

    #lfooter {
        font-size: 9px;
        margin-top: 60px;
        width: 100% !important;
        font-size:12px;
        border:none;
    }


    #f1 {
        float: left;
        font-size:10px;
        border: none;
    }

    #f2 {
        font-size:10px;
        text-align: right;
        border: none;
    }

    #f3 {
        font-size:10px;
        text-align: right;
        border: none;
    }



    </style>
</head>
<body>

    {{-- @if ($print_view)
    <img id="zcmclogo" src="{{ asset('storage/logo/zcmc.jpeg') }}" alt="zcmcLogo">
    <img id="dohlogo" src="{{ asset('storage/logo/doh.jpeg') }}" alt="dohLogo">
@else
    <img id="zcmclogo" src="{{ base_path() . '\public\storage\logo/zcmc.jpeg' }}" alt="zcmcLogo">
    <img id="dohlogo" src="{{ base_path() . '\public\storage\logo/doh.jpeg' }}" alt="dohLogo">
@endif --}}
<img id="zcmclogo" src="{{ base_path() . '\public\storage\logo/zcmc.jpeg' }}" alt="zcmcLogo">
<img id="dohlogo" src="{{ base_path() . '\public\storage\logo/doh.jpeg' }}" alt="dohLogo">

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

   <div class="">
    <div style="text-align: center;margin-top:20px;margin-bottom:20px;margin-right:5px" >
       <small> <b>OVERTIME AUTHORITY</b></small>
      </div>


        <table class="table-bordered"  cellspacing="0" cellpadding="10">
            <tbody>
       <tr>
                    <td class="text-start" colspan="6" style="font-weight: bold"> To :  {{$approvingOfficer->name}}</td>
                    <td class="text-start" colspan="6"> Date : {{$created}}
                    </td>
                </tr>

                <tr>
                    <td class="text-start" colspan="6"> Division : {{$approvingOfficer->designation}}</td>
                    <td class="text-start" colspan="6"> Office/Region : ZAMBOANGA CITY MEDICAL CENTER </td>
                </tr>

                <tr>
                    <td colspan="12"> Name of Employee's Authorized to Render Overtime </td>
                </tr>

                <tr>
                    <td colspan="12">


                        <table >
                            <tr style="border:none !important">
                                @foreach ($listofEmployees as $key => $item)
                                @if ($key % 7 == 0)
                                    {{-- Start a new <td> element --}}
                                    <td style="border:none !important">
                                @endif

                                {{ $item->name }}<br>

                                @if ($key % 7 == 6 || $key == count($listofEmployees) - 1)
                                    {{-- End the current <td> element --}}
                                    </td>
                                @endif
                            @endforeach

                            </tr>
                        </table>

                    </td>
                </tr>

                <tr>
                    <td colspan="12" style="text-align: center">
                        WORK PROGRAM
                    </td>
                </tr>

                <tr>
                    <td class="text-start" colspan="12">
                        Purpose of Overtime: {{$purposeofovertime}}

                    </td>
                </tr>

                <tr style="text-align: center;">
                    <td style="font-size:9px !important" colspan="5" rowspan="2" >ACTIVITIES TO BE ACCOMPLISHED</td>
                    <td style="font-size:11px !important" colspan="1" rowspan="1">Est.
                    <td style="font-size:11px !important" colspan="1" rowspan="2"> Est. MH Needed</td>
                    <td style="font-size:11px !important" colspan="1" rowspan="2"> Period Covered</td>
                    <td style="font-size:9px !important" colspan="4" rowspan="2"> PERSONS ASSIGNED</td>
                 </tr>

                 <tr style="text-align: center">
                    <td style="font-size:11px !important" colspan="1" rowspan="1">Qty.</td>
                 </tr>

                 {{-- Contents --}}

                 @foreach ($activities as $item)
                 @include('overtimeContents')
                 @endforeach





                 <tr style="border-top:2px solid rgb(173, 173, 173)">
                    <td colspan="5">

                        <span style="margin-bottom: 0%">

                            <span style="font-size:10px"> REQUESTED BY:</span>
                            <br>

                         <div style="text-align: center;font-size:13px;margin-top:25px">
                           <span style="text-transform:uppercase;font-size:11px"> {{$requestedBy->name}}</span>
                            <br>
                            <small>{{$requestedBy->designation_name}}</small>

                         </div>
                        </span>

                    </td>

                    <td colspan="7" rowspan="2" style="vertical-align: top; text-align: left;"> Condition:
                    <div style="position:relative;left:15%;;width:380px;">
                            <span>
                                The above names are hereby authorized to render overtime, subject to the following:
                                <br>
                                1 That the overtime work shall be rendered only after the authority to render overtime has been issued
                                <br>
                                2 That the overtime shall be personally supervised by duly designated overtime supervisor
                                <br>
                                3 That funds are available for this purpose
                                <br>
                                4 Monthly overtime pay should not exceed 50% of employee's monthly basic salary
                                <br>
                                5 Over-time pay for permanent employees should be in the form of COC.
                                </span>
                        </div>
                    </td>
                 </tr>

                 {{-- <tr>
                    <td colspan="4">



                            <span style="margin-bottom: 0%">


                             <div style="text-align: center;font-size:13px;margin-top:25px">
                               <span style="text-transform:uppercase">Nursing Assistant</span>
                                <br>
                                <small>Position</small>

                             </div>
                            </span>



                    </td>
                 </tr>  --}}

                 <tr>
                    <td colspan="5" >

                            <span style="margin-bottom: 0%">
                               <span style="font-size:10px"> RECOMMENDING APPROVAL :</span>
                                <br>

                             <div style="text-align: center;font-size:13px;margin-top:25px">
                               <span style="text-transform:uppercase;font-size:11px"> {{$recommendingofficer->name}}</span>
                                <br>
                                <small>{{$recommendingofficer->designation}}</small>

                             </div>
                            </span>


                    </td>
                 </tr>

                 <tr>
                    <td colspan="5" style="font-size:10px"> APPROVED BY: </td>
                    <td colspan="7" style="font-size:10px"> DURATION OF OVERTIME WORK</td>
                 </tr>

                 <tr  >
                    <td colspan="5" rowspan="2">


                            <div class="text-decoration-offset" style="margin-bottom: 2%;text-align:center;margin-top:25px">
                                <span style="text-transform:uppercase;font-size:11px"> {{$approvingOfficer->name}}</span>
                                <br>
                                <small>{{$approvingOfficer->designation}}</small>

                            </div>

                    </td>
                    <td colspan="3" class="cell-height-1">Period</td>
                    <td colspan="4" class="cell-height-1">Time</td>
                 </tr>

                 <tr>
                    <td colspan="3" >

                        <div style="position:relative;width:280px;font-size:10px">
                            @foreach ($activities as $item)
                        {{date("M j",strtotime($item->dates[0]->date))}}
                        to
                            {{date("M j",strtotime($item->dates[count($item->dates)-1]->date))}},
                        @endforeach
                        </div>


                    </td>
                    <td colspan="4" >
                        <div style="position:relative;width:280px;font-size:10px">
                        @foreach ($activities as $item)
                        @php
                        $timeRanges = [];
                    foreach ($item->dates as $date) {
                        $timeRange = date("H:i a", strtotime($date->time_from)) . ' - ' . date("H:i a", strtotime($date->time_to));
                        $timeRanges[] = $timeRange;
                    }
                    $uniqueTimeRanges = array_unique($timeRanges);
                    foreach ($uniqueTimeRanges as $timeRange) {
                        echo $timeRange . ', ';
                    }

                        @endphp
                        @endforeach
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <table id="lfooter">
            <tr style=" border:none;">
                <td id="f1">ZCMC-F-HRM0-12</td>
                <td id="f2">ReV.0</td>
                <td id="f3">Effectivity Date: June 2, 2014</td>
            </tr>
        </table>

    </div>
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script> --}}
</body>
</html>

