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
        tr th {
            padding: 5px
        }
        th, td {
            border: 1px solid #ddd;
            text-align: center
            /* padding: 4px; */


        }
        tbody tr td {
            padding: 5px
        }
        tr, td {
            border: 1px solid  #ddd;
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


{{-- <img id="zcmclogo" src="{{ base_path() . '\public\storage\logo/zcmc.jpeg' }}" alt="zcmcLogo">
<img id="dohlogo" src="{{ base_path() . '\public\storage\logo/doh.jpeg' }}" alt="dohLogo"> --}}
<img id="zcmclogo" src="{{ asset('storage/logo/zcmc.jpeg') }}" alt="zcmcLogo">
<img id="dohlogo" src="{{ asset('storage/logo/doh.jpeg') }}" alt="dohLogo">
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

       <small style="font-size: 12px"> <b>LIST OF PERSONNEL WHO ARE ENTITLED FOR COMPENSATORY OVERTIME CREDIT</b></small>
      </div>

      <table>
        <thead>
              <tr>
            <th rowspan="2">
                NAME
            </th>
            <th rowspan="2">
                POSITION
            </th>
            <th>

                DTR
            </th>
            <th>
                No. OF HOURS
            </th>
            <th rowspan="2">
                REMARKS
            </th>
        </tr>
        <tr>
            <th>
                REG SCHEDULE

            </th>
            <th>
                OVERTIME
            </th>
        </tr>
        </thead>
        <tbody>

                  <tr>
                <td>
                   {{$employees->employee_profile->name}}
                </td>
                <td>
                    {{$employees->employee_profile->designation_code}}
                </td>
                <td>
                    Off - Duty
                </td>
                <td>
                    {{date('h:i a',strtotime(date('Y-m-d').' '.$time_from.':00'))}} - {{date('h:i a',strtotime(date('Y-m-d').' '.$time_to.':00'))}}
                </td>
                <td>
                    {{$employees->remarks}}
                </td>
            </tr>






        </tbody>



      </table>

      <table style="border:none;margin-top:120px">
        <tr style="border:none">
            <td style="width: 300px;border:none">
                <h4 style="margin-top:15px;font-weight:normal;margin-right:100px">Prepared By :</h4>
                <h3 style="margin-top: 20px">{{$preparedBy->name}}
                    <br>
                    <small style="font-weight: normal">{{$preparedBy->designation_name}}</small>

                </h3>

            </td>
            <td style="width: 300px;border:none">
                <h4 style="margin-top:15px;font-weight:normal;margin-right:100px">Recommending Approval :</h4>
                <h3 style="margin-top: 20px">{{$recommendingOfficer->name}}
                    <br>
                    <small style="font-weight: normal">{{$recommendingOfficer->designation}}</small>

                </h3>

            </td>
            <td style="width: 300px;border:none">
                <h4 style="margin-top:15px;font-weight:normal;margin-right:100px">Approved By :</h4>
                <h3 style="margin-top: 20px">{{$approvingOfficer->name}}
                    <br>
                    <small style="font-weight: normal">{{$approvingOfficer->designation}}</small>

                </h3>

            </td>

        </tr>
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

