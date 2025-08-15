<style>
    @import url('https://fonts.googleapis.com/css2?family=Onest:wght@200&display=swap');

    body {
        display: flex;
        justify-content: center;
        font-family: 'Onest', sans-serif;
        user-select: none;
    }

    #po {
        width: 695px;
        padding: 5px;
    }

    #titleBar {
        text-align: center;
        font-size: 9px;
        font-weight: 350;
        margin-bottom: 5px;
    }

    #zcmc {
        font-size: 11px;
        font-weight: 450;
    }

    #addr {
        font-size: 8px;
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

    #userName {
        text-align: center;
        text-transform: uppercase;
        margin-top: -20px;
        font-size: 12px;

        font-weight: bold
    }

    #userName div {

        height: 1.5px;
        width: 100%;
        background-color: gray;

    }

    #userName span {
        font-size: 10px;
        font-weight: 520;
        font-weight: bold;
    }

    .ftmo {
        display: flex;
        width: 100%;
        font-weight: normal;
        font-weight: bold
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
        font-size: 11px;

    }

    .ot {
        font-size: 10px;
        font-weight: bold
    }

    #zcmclogo {
        width: 35px;
        float: left;
    }

    #dohlogo {
        width: 50px;
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
        font-size: 9px !important;

    }

    /* Style table headers (th) */
    #tabledate th {
        background-color: #f2f2f2;
        /* Background color for header cells */
        font-size: 9px;
        font-weight: 520;
        text-align: center;

        text-transform: uppercase;

        /* Add padding to headers for spacing */

    }

    /* Style table data cells (td) */
    #tabledate td {
        text-align: center;
        border: 1px solid rgb(158, 153, 153);

        /* Add padding to data cells for spacing */
        font-size: 9px !important;
        width: 38px !important;
        height: 21px !important;

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
        font-size: 10px;
        line-height: 1;
    }

    .signature {

        text-align: center;
        margin-top: 2px;

    }

    .signature .line {
        height: 2px;
        background-color: gray;
        width: 60%;

        margin-left: 20%;


    }

    .signature span {
        font-size: 11px
    }

    .footer {
        margin-top: 20px;
    }

    .footer span {
        font-size: 10px;


    }

    #lfooter {
        font-size: 9px;
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
        color: black;
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

    #headertop {
        border-bottom: 1px solid rgb(197, 194, 194);
        border-top: 1px solid rgb(197, 194, 194);
        font-weight: bold
    }

    #headertop th {
        font-size: 11px;
        font-weight: bold;
        color: #656f74
    }

    #tabledate tbody tr td {
        padding:8px;
        font-size: 10px !important;
    }
</style>

<div id="po">


    <div id="titleBar">
        {{-- @if ($print_view)
        <img id="zcmclogo" src="{{ asset('storage/logo/zcmc.jpeg') }}" alt="zcmcLogo">
        <img id="dohlogo" src="{{ asset('storage/logo/doh.jpeg') }}" alt="dohLogo">
    @else
        <img id="zcmclogo" src="{{ base_path() . '\public\storage\logo/zcmc.jpeg' }}" alt="zcmcLogo">
        <img id="dohlogo" src="{{ base_path() . '\public\storage\logo/doh.jpeg' }}" alt="dohLogo">
    @endif --}}
    <img id="zcmclogo" src="{{ base_path() . '\public\storage\logo/zcmc.jpeg' }}" alt="zcmcLogo">
        <img id="dohlogo" src="{{ base_path() . '\public\storage\logo/doh.jpeg' }}" alt="dohLogo">
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




    <div id="userName">

    </div>





    <h4 style="margin-top:70px;text-align:center">
        DTR Logs
    <br>
    <span style="font-size:11px;color:#656f74">
        Device Daily Time Records
        <br>
        User Management Information System
        <br>
       <span style="font-size:15px;color:#0B60B0"> {{date('F j, Y',strtotime($dtr['dtr_date']))}}</span>
    </span>
    </h4>



    <table style="width: 100%;text-align:center;margin-bottom:20px">
        <tr>
            <td>  {{$Name}}</td>
            <td> <span style="font-size:13px">Print Date : {{date('m-d-Y')}}</span></td>
        </tr>
        <tr>
            <td style="font-size:12px">{{$designation->name}} <br>
                {{$empID}}
            </td>
            <td> <span style="font-size:13px"></span></td>
        </tr>
    </table>

    <table id="tabledate">
        <tr id="headertop">
           
            <th  style="text-align: center">
            
          LOG
            <br>
            <span style="font-size:9px">Time Registered</span>
             
            </th>
            <th  style="text-align: center;width:140px">
               Pulled
            <br>
            <span style="font-size:9px">Time Pulled by Device</span>
            </th>
            <th style="text-align: center;width:150px">Device Name</th>
          
        </tr>

        {{-- {{print_r($dtrRecords)}} --}}
        <tbody>
                @php
                      //dtr_date 
        //date_time,datepull,device_name, status_description->description,entry_status
                @endphp

                @foreach ($dtr['logs'] as $item)
                <tr >
                  
                    <td  style="text-align: center; font-weight:bold">
                        <span style="font-weight: bold">{{date('h:i a',strtotime($item->date_time))}}</span>
                    </td>
                    <td  style="text-align: center;font-weight:bold">{{date('h:i a',strtotime($item->datepull))}}</td>
                    <td style="text-align: center">{{$item->device_name}}</td>
                  
                </tr>
                @endforeach
          
        </tbody>
    </table>



</div>

