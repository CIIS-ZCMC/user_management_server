<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        @page {



            /* Set the page size to auto to adjust based on content */
            margin: 0 !important;
            padding: 20px !important;
        }

        body {
            margin: 0;
            /* Remove any margin on the body */
            padding: 0;
            /* Remove any padding on the body */
        }

        #tbleformat {

            /* Make the table width 100% of the page */
            margin-left: 1.4px;
            border-collapse: collapse;

            /* Collapse table borders to remove spacing */
        }

        #tbleformat tr td {
            padding: 0;
            margin: 0;

            /* Add borders for demonstration purposes */
        }
    </style>

    @if (isset($data))
        <style>
            @page {
                size: A4;
            }

            #btnprint {
                position: fixed;
                top: 20px;
                right: 50px;
                width: 200px;
                padding: 10px;
                background-color: #3887BE;
                border: none;
                outline: none;
                font-size: 16px;
                color: #FAEED1;
                text-transform: uppercase;
                border-radius: 5px;
                font-weight: normal;
                transition: all 0.4s;

            }

            #btnprint:hover {
                background-color: rgb(27, 121, 161);
                letter-spacing: 1px;
                cursor: pointer;
            }
        </style>
    @endif
    <script>
        // window.print()
    </script>
</head>

<body>

    {{-- @if (isset($data))
    <button id="btnprint" onclick="openPrintDialog()">Print-DTR</button>
    @endif --}}


    <table id="tbleformat">

        @if (isset($data))
            @foreach ($data as $key => $item)
                @php

                    $daysInMonth = $item['daysInMonth'];
                    $year = $item['year'];
                    $month = $item['month'];
                    $firstin = $item['firstin'];
                    $firstout = $item['firstout'];
                    $secondin = $item['secondin'];
                    $secondout = $item['secondout'];
                    $undertime = $item['undertime'];
                    $OHF = isset($item['emp_Details'][$key]['OHF'][$key])
                        ? $item['emp_Details'][$key]['OHF'][$key]
                        : '';
                    $Arrival_Departure = isset($item['emp_Details'][$key]['Arrival_Departure'])
                        ? $item['emp_Details'][$key]['Arrival_Departure']
                        : '';
                    $Employee_Name = isset($item['emp_Details'][$key]['Employee_Name'])
                        ? $item['emp_Details'][$key]['Employee_Name']
                        : '';
                    $dtrRecords = $item['dtrRecords'];
                    $holidays = $item['holidays'];
                    $print_view = $item['print_view'];
                    $halfsched = $item['halfsched'];
                    $biometric_ID = isset($item['emp_Details'][$key]['biometric_ID'])
                        ? $item['emp_Details'][$key]['biometric_ID']
                        : '';
                    $employeeSched = $item['schedule'];
                    $incharge = $item['Incharge'];
                    $leaveapp = $item['leaveapp'];
                    $obApp = $item['obApp'];
                    $otApp = $item['otApp'];
                    $ctoApp = $item['ctoApp'];


                @endphp
                <tr>


                    <td style="border-right: 1px solid black;">

                        @include('generate_dtr.dtrformat', [
                            'schedule' => $employeeSched,
                            'Incharge' => $incharge,
                        ])
                    </td>
                    <td>
                        @include('generate_dtr.dtrformat', [
                            'schedule' => $employeeSched,
                            'Incharge' => $incharge,
                        ])
                    </td>
                </tr>
                <tr>
                    <td>
                        <hr>
                    </td>
                    <td>
                        <hr>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td style="border-right: 1px solid black;">

                    @include('generate_dtr.dtrformat')
                </td>
                <td>
                    @include('generate_dtr.dtrformat')
                </td>
            </tr>
        @endif
    </table>




</body>

</html>
