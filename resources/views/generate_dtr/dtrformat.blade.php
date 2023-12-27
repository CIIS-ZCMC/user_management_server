<style>
    @import url('https://fonts.googleapis.com/css2?family=Onest:wght@200&display=swap');
    body {
        display: flex;
        justify-content: center;
        font-family: 'Onest', sans-serif;
        user-select: none;
    }

    #po {
        width: 395px;
        padding: 5px;
    }

    #titleBar {
        text-align: center;
        font-size: 10px;
        font-weight: 350;
        margin-bottom: 5px;
    }

    #zcmc {
        font-size: 13px;
        font-weight: 450;
    }

    #addr {
        font-size: 10px;
        font-weight: 350;
    }

    #header {
        text-align: center;
        margin-top: -10px;
    }

    #header h6 {
        font-size: 15px;
        letter-spacing: 1px;
    }

    #userName {
        text-align: center;
        text-transform: uppercase;
        margin-top: -20px;
        font-size: 15px;
        font-weight: 500;

    }

    #userName div {

        height: 1.5px;
        width: 100%;
        background-color: gray;
    }

    #userName span {
        font-size: 13px;
        font-weight: 520;
    }

    .ftmo {
        display: flex;
        width: 100%;
        font-weight: normal;
    }

    .ftmo>* {
        flex-grow: 1;
        /* Makes all items expand equally */
        flex-basis: 0;
        /* Distributes available space equally among items */
        max-width: 100%;
        /* Ensure that items don't exceed the container width */
    }

    .ftmo span {
        font-size: 13px;
        text-transform: uppercase;
    }

    #f1 {
        margin-top: 2px;
    }

    #f2 {
        text-align: center !important;
    }

    #f2 div {
        height: 1.5px;
        background-color: gray;
    }

    .tit {
        font-weight: 500;
        font-size: 13px
    }

    .ot {
        font-size: 12px
    }

    #zcmclogo {
        width: 45px;
        float: left;
    }

    #dohlogo {
        width: 60px;
        float: right;
    }

    /* Apply styling to the entire table */
    #tabledate {
        width: 98%;
        margin-left: 1%;
        border-collapse: collapse;
        /* Combine adjacent borders into a single border */

    }

    /* Style table rows */
    #tabledate tr {
        border: 1px solid gray;

    }

    /* Style table headers (th) */
    #tabledate th {
        background-color: #f2f2f2;
        /* Background color for header cells */
        font-size: 9px;
        font-weight: 520;
        text-align: center;
        padding: 2px;
        text-transform: uppercase;

        /* Add padding to headers for spacing */

    }

    /* Style table data cells (td) */
    #tabledate td {
        text-align: center;

        /* Add padding to data cells for spacing */
        font-size: 12px;
        border: 1px solid black;
        text-transform: uppercase;


    }

    /* Alternate row background color for better readability */
    #tabledate tr:nth-child(even) {
        /* background-color: #e0e0e0; */
    }


    .certification {
        text-align: left;
        margin-top: -10px;
    }

    .certification p {
        font-size: 13px;
        line-height: 1;
    }

    .signature {

        text-align: center;
        margin-top: 15px;

    }

    .signature .line {
        height: 2px;
        background-color: gray;
        width: 60%;

        margin-left: 20%;


    }

    .signature span {
        font-size: 13px
    }

    .footer {
        margin-top: 20px;
    }

    .footer span {
        font-size: 12px;


    }

    #lfooter {
        font-size: 11px;
        width: 100% !important;
    }

    #f1 {
        float: left;
    }

    #f2 {

        text-align: right;
    }

    #f3 {
        text-align: right;
    }

    .fentry {
        color: #12486B;
        font-weight: bold
    }

    #tblheader {
        border-collapse: collapse;
    }

    #tblheader tr td {
        padding: 5px;
        border: 1px solid gray;

        text-transform: capitalize;
    }
</style>

<div id="po">
    {{--  d:\ciisDTR\dtr\storage\app\public\logo\doh.jpeg d:\ciisDTR\dtr\storage\app\public\logo\zcmc.jpeg resources/views/logo/zcmc.jpeg  --}}
    @if ($print_view)
    <img id="zcmclogo" src="{{ asset('storage/logo/zcmc.jpeg') }}" alt="zcmcLogo">
    <img id="dohlogo" src="{{ asset('storage/logo/doh.jpeg')}}" alt="dohLogo">
    @else
    <img id="zcmclogo" src="{{ base_path() . '\public\storage\logo/zcmc.jpeg'}}" alt="zcmcLogo">
    <img id="dohlogo" src="{{ base_path() . '\public\storage\logo/doh.jpeg'}}" alt="dohLogo">
    @endif


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



    <div id="header">
        <h6>DAILY TIME RECORD</h6>
    </div>

    <div id="userName">
        {{$Employee_Name}}
        <div></div>
        <span>NAME</span>
    </div>


    <table style="width:100% !important;">
        <tr>
            <td class="tit">
                <span>
                    For the month of
                </span>
            </td>
            <td class="ot">

                <div id="f2">
                    <span>{{date('F',strtotime($year.'-'.$month.'-1'))}} 1 to {{$daysInMonth}} {{$year}}</span>
                    <div></div>

                    <span>Regular Days</span>
                </div>

            </td>
        </tr>

        <tr>
            <td class="tit">
                <span>
                    Official hours for
                </span>
            </td>
            <td class="ot">
                : {{$OHF}}
            </td>
        </tr>

        <tr>
            <td class="tit">
                <span>
                    Arrival and Departure
                </span>
            </td>
            <td class="ot">
                : {{$Arrival_Departure}}
            </td>
        </tr>


    </table>
    <hr>


    <table id="tabledate">
        <tr>
            <th>

            </th>
            <th>

            </th>
            <th>Arrival</th>
            <th>Departure</th>
            <th>Arrival</th>
            <th>Departure</th>
            <th>
                UNDERTIME
                <table id="tblheader">
                    <tr>
                        <td>Hours</td>
                        <td>Minutes</td>
                    </tr>
                </table>
            </th>
        </tr>

        {{-- {{print_r($dtrRecords)}} --}}
        <tbody>
            @php
                $isExcept = false;
            @endphp
            @for($i = 1; $i <= $daysInMonth; $i++)

            @php
            $checkIn = array_filter($dtrRecords, function ($res) use ($i) {
                return date('d', strtotime($res['first_in'])) == $i
                    && date('d', strtotime($res['first_out'])) == $i + 1;
            });

            $val = 0;
            $outdd = array_map(function($res) {
                return [
                    'first_out' => $res['first_out']
                ];
            }, $checkIn);
            @endphp

            <tr>
                <td>{{$i}}</td>
                <td style="text-transform: capitalize; color:#05171f; font-size:10px">
                    {{date('D', strtotime(date('Y-m-d', strtotime($year.'-'.$month.'-'.$i))))}}
                </td>

                @php $rowspan = count($outdd) > 0 ? 2 : 1; @endphp

                @if ($rowspan > 1)
                    @php
                        $isExcept = true;
                    @endphp

                 @include('generate_dtr.tableDtr_datespan')
                @else
                    @if ($isExcept == true)

                        @php
                            $isExcept = false;
                        @endphp
                    @else
                      @include('generate_dtr.tableDtr_date')
                    @endif
                @endif

                @if (count($checkIn) >= 1)
                    @php $val = $i; @endphp
                @endif
            </tr>
        @endfor
        </tbody>
    </table>
    <div class="certification">
        <p>I certify on my honor that the above is a true and correct report of the hours of work performed, recorded daily at the time of arrival and departure from the office.</p>
    </div>
    <br>
    <div class="signature">
        <div>

        </div>
        <div class="line"></div>
        <span> Verified as to prescribed hours</span>
    </div>
    <br><br>
    <div class="signature">
        <div>

        </div>
        <div class="line"></div>
        <span> In Charge</span>
    </div>

    <div class="footer">
        <span>Adopted from CSC FORM NO. 48</span>
        <br><br>
        <table id="lfooter">
            <tr>
                <td id="f1">ZCMC-F-HRMO-01</td>
                <td id="f2">ReV.0</td>
                <td id="f3">Effectivity Date: June 2, 2014</td>
            </tr>
        </table>

    </div>

</div>

<script>
    document.addEventListener("keydown", function (event) {
  // Check if the pressed key is F11 (key code 122)
  if (event.keyCode === 123) {
    event.preventDefault(); // Prevent the default action (toggling full-screen)
  }
});

// Listen for the contextmenu event (right-click) to prevent opening the context menu
document.addEventListener("contextmenu", function (e) {
  e.preventDefault();
});

// Listen for the keyboard shortcuts that open the developer tools (F12, Ctrl+Shift+I, Ctrl+Shift+J)
document.addEventListener("keydown", function (e) {
  if (e.key === "F12" || (e.ctrlKey && e.shiftKey && (e.key === "I" || e.key === "J"))) {
    e.preventDefault();
  }
});

// Attempt to close the developer tools if they are open
function closeDeveloperTools() {
  if (typeof window !== "undefined") {
    // This will only work in some browsers (not guaranteed)
    window.close();
  }
}

// Call the function to close the developer tools
closeDeveloperTools();

</script>
