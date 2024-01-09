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
            padding: 8px;
            text-align: center;
        }
        tr, td {
            border: 2px solid #000000;
            padding: 8px;
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
        <!-- Add any other header content as needed -->
    </header>

    <div class="container-fluid">
        
        <div  style="text-align: center;">
            <small> <b> OVERTIME AUTHORITY </b> </small>
        </div>

        <table class="table-bordered" border="1" cellspacing="0" cellpadding="10">
            <tbody>
                <tr>
                    <td class="text-start" colspan="5"> To : </td>
                    <td class="text-start" colspan="5"> Date : </td>
                </tr>

                <tr>
                    <td class="text-start" colspan="5"> Division : </td>
                    <td class="text-start" colspan="5"> Office/Region : </td>
                </tr>

                <tr>
                    <td colspan="10"> Name of Employee's Authorized to Render Overtime </td>
                </tr>

                <tr>
                    <td colspan="10">
                        <div class="mb-3 text-start">
                            <small>1. Juan</small>
                            <small>2. Two</small>
                            <small>3. Tree</small>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td colspan="10">
                        WORK PROGRAM
                    </td>
                </tr>

                <tr>
                    <td class="text-start" colspan="10">
                        Purpose of Overtime: 
                    </td>
                </tr>

                <tr>
                    <td colspan="3" rowspan="2">ACTIVITIES TO BE ACCOMPLISHED</td>
                    <td colspan="2" rowspan="1">Est.
                    <td colspan="2" rowspan="2"> Est. MH Needed</td>
                    <td colspan="2" rowspan="2"> Period Covered</td>
                    <td colspan="2" rowspan="2"> PERSONS ASSIGNED</td>
                 </tr>

                 <tr>
                    <td colspan="2" rowspan="1">Qty.</td>
                 </tr>

                 <tr>
                    <td colspan="3">
                        <div class="mb-3">
                            <h6 class="text-start">REQUESTED BY:</h6> <br>
                            <h6 class="text-decoration-offset" style="margin-bottom: 0%">Juanito J. Juanita</h6>
                            <small>Name</small>
                        </div>
                    </td>

                    <td colspan="7" rowspan="3" style="vertical-align: top; text-align: left;"> Condition:
                        <div class="mb-3" style="margin-left: 10%">
                            <p> Content</p>
                        </div>
                    </td>
                 </tr>

                 <tr>
                    <td colspan="3">
                        <div class="mb-3"> <br>
                            <h6 class="text-decoration-offset"  style="margin-bottom: 0%">Nursing Assistant</h6>
                            <small>Position</small>
                        </div>
                    </td>
                 </tr>

                 <tr>
                    <td colspan="3">
                        <div class="mb-3">
                            <h6 class="text-start">RECOMMENDING APPROVAL :</h6> <br>
                            <h6 class="text-decoration-offset" style="margin-bottom: 0%">Juanita J. Juanito</h6>
                            <small>Finance and Management Officer II</small>
                        </div>
                    </td>
                 </tr>

                 <tr>
                    <td colspan="3"> APPROVED BY: </td>
                    <td colspan="7"> DURATION OF OVERTIME WORK</td>
                 </tr>

                 <tr>
                    <td colspan="3" rowspan="3">
                        <div class="mb-3"> <br>
                            <h6 class="text-decoration-offset" style="margin-bottom: 0%">AFDAL B. KUNTING, MD, MPH, FPCP</h6>
                            <small>Finance and Management Officer II</small>
                        </div>
                    </td>
                    <td colspan="4"> Period </td>
                    <td colspan="3"> Time </td>
                 </tr>

                 
                 <tr>
                    <td colspan="4">
                        Febuary 24-15, 2023
                    </td>
                    <td colspan="3">
                        08:00 AM to 05:00 PM 
                    </td>
                 </tr>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>

