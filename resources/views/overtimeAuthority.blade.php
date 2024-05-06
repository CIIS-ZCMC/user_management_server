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

   <div class="container-fluid">

        <div  style="text-align: center;margin-bottom:10px">
            <small> <b> OVERTIME AUTHORITY </b> </small>
        </div>

        <table class="table-bordered"  cellspacing="0" cellpadding="10">
            <tbody>
       <tr>
                    <td class="text-start" colspan="5" style="font-weight: bold"> To : AFDAL B. KUNTING. MD. MPH, FPCP </td>
                    <td class="text-start" colspan="5"> Date : November 23, 2023
                    </td>
                </tr>

                <tr>
                    <td class="text-start" colspan="5"> Division : Medical Center Chief II </td>
                    <td class="text-start" colspan="5"> Office/Region : ZAMBOANGA CITY MEDICAL CENTER </td>
                </tr>

                <tr>
                    <td colspan="10"> Name of Employee's Authorized to Render Overtime </td>
                </tr>

                <tr>
                    <td colspan="10">


                        <table >
                            <tr style="border:none !important">
                                <td style="border:none !important">
                                    Fritzie Lynn T. Cabilin
                                    <br>
                                    Vanessa N. De Castro
                                    <br>
                                    Hany Vincent WS Del Castillo
                                    <br>
                                    Kristine Joy L. Fernandez
                                    <br>
                                    Ma. Thereza D. Francisco
                                    <br>
                                    Ghia Riz G. Mayonlla
                                    <br>
                                    Arnie Chelle DC Montuerto

                                </td>
                                <td style="border:none !important">
                                    Fritzie Lynn T. Cabilin
                                    <br>
                                    Vanessa N. De Castro
                                    <br>
                                    Hany Vincent WS Del Castillo
                                    <br>
                                    Kristine Joy L. Fernandez
                                    <br>
                                    Ma. Thereza D. Francisco
                                    <br>
                                    Ghia Riz G. Mayonlla
                                    <br>
                                    Arnie Chelle DC Montuerto

                                </td>
                                <td style="border:none !important">
                                    Fritzie Lynn T. Cabilin
                                    <br>
                                    Vanessa N. De Castro
                                    <br>
                                    Hany Vincent WS Del Castillo
                                    <br>
                                    Kristine Joy L. Fernandez
                                    <br>
                                    Ma. Thereza D. Francisco
                                    <br>
                                    Ghia Riz G. Mayonlla
                                    <br>
                                    Arnie Chelle DC Montuerto

                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                <tr>
                    <td colspan="10" style="text-align: center">
                        WORK PROGRAM
                    </td>
                </tr>

                <tr>
                    <td class="text-start" colspan="10">
                        Purpose of Overtime: Closing of accounting books and preparation of Financial Statements for FY 2023

                    </td>
                </tr>

                <tr style="text-align: center;">
                    <td style="font-size:11px !important" colspan="3" rowspan="2" >ACTIVITIES TO BE ACCOMPLISHED</td>
                    <td style="font-size:11px !important" colspan="2" rowspan="1">Est.
                    <td style="font-size:11px !important" colspan="1" rowspan="2"> Est. MH Needed</td>
                    <td style="font-size:11px !important" colspan="1" rowspan="2"> Period Covered</td>
                    <td style="font-size:11px !important" colspan="3" rowspan="2"> PERSONS ASSIGNED</td>
                 </tr>

                 <tr style="text-align: center">
                    <td style="font-size:11px !important" colspan="2" rowspan="1">Qty.</td>
                 </tr>

                 {{-- Contents --}}

                @include('overtimeContents')
                @include('overtimeContents')



                 <tr style="border-top:2px solid rgb(173, 173, 173)">
                    <td colspan="3">

                        <span style="margin-bottom: 0%">
                            REQUESTED BY:
                            <br>

                         <div style="text-align: center;font-size:13px;margin-top:25px">
                           <span style="text-transform:uppercase"> Juanita J. Juanito</span>
                            <br>
                            <small>Finance and Management Officer II</small>

                         </div>
                        </span>

                    </td>

                    <td colspan="7" rowspan="3" style="vertical-align: top; text-align: left;"> Condition:
                    <div style="position:relative;left:10%;top:1%;width:380px;">
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

                 <tr>
                    <td colspan="3">



                            <span style="margin-bottom: 0%">


                             <div style="text-align: center;font-size:13px;margin-top:25px">
                               <span style="text-transform:uppercase">Nursing Assistant</span>
                                <br>
                                <small>Position</small>

                             </div>
                            </span>



                    </td>
                 </tr>

                 <tr>
                    <td colspan="3" >

                            <span style="margin-bottom: 0%">
                                RECOMMENDING APPROVAL :
                                <br>

                             <div style="text-align: center;font-size:13px;margin-top:25px">
                               <span style="text-transform:uppercase"> Juanita J. Juanito</span>
                                <br>
                                <small>Finance and Management Officer II</small>

                             </div>
                            </span>


                    </td>
                 </tr>

                 <tr>
                    <td colspan="3"> APPROVED BY: </td>
                    <td colspan="7"> DURATION OF OVERTIME WORK</td>
                 </tr>

                 <tr  >
                    <td colspan="3" rowspan="2">

                            <div class="text-decoration-offset" style="margin-bottom: 2%;text-align:center;margin-top:25px">

                                AFDAL B. KUNTING, MD, MPH, FPCP
                                <br>
                                <small>Finance and Management Officer IIss</small>

                            </div>

                    </td>
                    <td colspan="4" class="cell-height-1">Period</td>
                    <td colspan="3" class="cell-height-1">Time</td>
                 </tr>

                 <tr>
                    <td colspan="4" class="cell-height-1">
                        Febuary 24-15, 2023
                    </td>
                    <td colspan="3" class="cell-height-1">
                        08:00 AM to 05:00 PM
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script> --}}
</body>
</html>

