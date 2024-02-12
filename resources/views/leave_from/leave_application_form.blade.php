<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LEAVE REPORT - {{$data->employeeProfile->personalInformation->last_name ?? null }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            width: 8.5in; /* Width of a standard long bond paper in inches */
            height: 14in; /* Height of a standard long bond paper in inches */
            margin-left: 0;
            margin-right: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
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
            width: 90%;
            border-collapse: collapse;
            margin: 0;
            /* text-align: left; */
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        tr, td {
            border: 2px solid #000000;
            padding: 2px;
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

        .topleft {
            font-size: 13px;
            font-weight: bold;
            vertical-align: top;
            text-align: left;"
        }

        .topcenter {
            font-size: 13px;
            font-weight: lighter;
            vertical-align: center;
            text-align: center;"
        }

        .text-end {
            text-align: right;"
        }

        .rigthside-font {
            font-size: 11px;
            font-weight: bolder;
        }

        .small {
            font-size: 12px;
        }

        ul , li, span {
            font-size: 10px;
            font-weight: bold;
        }

        .small-underline {
            border-bottom: 1px solid #000; 
            display: inline-block; 
            width: 15px;
            text-align: center;
        }

        .small-table {
            width: 90%;
            margin: auto;
            text-align: center;
        }
        .small-table, th, td {
            font-size: 13px;
            border: 1px solid #000000;
            padding: 2px;
            text-align: center;
        }
        .small-table, tr, td {
            font-size: 10px;
            border: 1px solid #000000;
            padding: 2px;
        }
        .small-p {
            font-size : 10px;
            display: block;
            /* word-wrap: break-word; */
            /* white-space:pre-wrap; */
        }

        .name-row {
            text-align: center;
        }

        .label {
            font-weight: bold;
            padding-right: 10px;
        }

        .name-column,
        .formatted-name-column {
            border: 1px solid #ccc; /* Example border for better visualization */
            padding: 10px;
            width: 33%; /* Equal width for each column */
        }

        .formatted-name-column {
            text-align: left;
        }
    </style>


</head>
<body>
    <div>
        <span style="display: block; font-size: 8px;">Civil Service Form No. 6</span>  
        <span style="display: block; font-size: 8px;">Revised 2020</span>
    </div>

<header style="width: 100%;display: flex; align-items: center;">
        <!-- ZCMC Logo on the left -->
        <img id="zcmclogo" src="{{ base_path() . '\public\storage\logo/zcmc.jpeg'}}" alt="ZCMC Logo" style="float: left; width: 40px;height:100px">
    
        <!-- Center Text -->
        <div style="float:left; width: 500px;text-align: center;  margin-left: 50px; font-weight: 300; line-height: 1">
            <p style="font-size: 10px; margin: 0;font-weight:lighter">Republic of the Philippines</p>
            <p style="font-size: 10px; margin: 0;font-weight:lighter">Department of Health</p>
            <p style="font-size: 12px; margin: 0;font-weight:lighter">ZAMBOANGA CITY MEDICAL CENTER</p>
            <p style="font-size: 10px; margin: 0;font-weight:lighter">Dr. Evangelista Street, Sta. Catalina, Zamboanga City</p>
        </div>
    
        <!-- DOH Logo on the right -->
        <img id="dohlogo" src="{{ base_path() . '\public\storage\logo/doh.jpeg'}}" alt="DOH Logo" style="float: right; margin-right:105px; width: 50px;height:150px">
    </header> 
    
    

    <div class="container-fluid">
        
        <div  style="text-align: center; margin-top:5px">
            <small> <b> APPLICATION FOR LEAVE </b> </small>
        </div>

        <table class="table-bordered" border="1" cellspacing="0" cellpadding="5">
            <tbody>
                <tr>
                    <td class="topleft" colspan="1" style="width: 35%;"> 1. OFFICE/AGENCY
                        <div class="mb-3 topcenter">
                            <label>
                                 Zamboanga City Medical Center
                            </label>
                        </div>
                    </td>
    

                    <td class="topleft" colspan="1" style="border-right:#ddd">
                        <label> 2. Name :</label>
                    </td>

                    <td class="topleft" colspan="2" style="border-left:#ddd; border-right:#ddd">
                        <label> (Last) </label> <br>
                        <label class="text-center" style="padding:4px"> {{ $data->employeeProfile->personalInformation->last_name ?? null }} </label>
                    </td>

                    <td class="topleft" colspan="2"style="border-left:#ddd; border-right:#ddd">
                        <label> (First) </label> <br>
                        <label class="text-center" style="padding:4px"> {{ $data->employeeProfile->personalInformation->first_name ?? null }} </label>
                    </td>

                    <td class="topleft" colspan="1" style="border-left:#ddd">
                        <label> (Middle) </label> <br>
                        <label class="text-center" style="padding:4px"> {{ $data->employeeProfile->personalInformation->last_name ?? null }} </label>
                    </td>
                </tr>

                <tr>
                    <td class="topleft" colspan="1"> 3. DATE OF FILING
                        <div class="mb-3 topcenter">
                            <label>
                                {{-- {{ date(' F d, Y', strtotime($data->created_at)) }} --}}
                            </label>
                        </div>
                    </td>

                    <td class="topleft" colspan="4" style="width: 33%"> 4. POSITION
                        <div class="mb-3 topcenter">
                            <label>
                                {{ $data->employeeProfile->findDesignation()['name'] }}
                            </label>
                        </div>
                    </td>

                    <td class="topleft" colspan="2"> 5. SALARY
                        <div class="mb-3 topcenter">
                            <label>
                            </label>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td colspan="7" style="font-size: 15px"><small><b> DETAILS OF APPLICATION </b></small></td>
                </tr>

                <tr>
                    <td class="topleft" colspan="3"> 6. A.) TYPE OF LEAVE TO BE AVAILED OF
                        <div class="mb-3 text-start" style="margin-top:5px">
                            @foreach ($leave_type as $leaveType)
                                <div style="display: flex; align-items:center; padding:1"> 
                                    <label>
                                        @if($leaveType->id === $data->leave_type_id)
                                        ( x )
                                        @else 
                                        ( &nbsp;&nbsp;  )
                                        @endif  
                                    </label>
                                    <label class="small" style="margin-left:1">
                                              
                                    {{ $leaveType->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </td>
                    
                    <td class="topleft" colspan="4"> 6. B.) DETAILS OF LEAVE <br>
                        <div class="mb-3 text-start small" style="margin-top:5px; margin-left:4px">
                            <label class="rigthside-font">In case of Vacation/Special Privilege Leave:</label>
                          
                            <div>
                                <span class="small-underline">
                                    @if ($data->country === 'Philippines')
                                        x
                                    @endif
                                </span>
                                <span style="font-size: 12px;">Within the Philippines</span>    
                                <span style="border-bottom: 1px solid #000; display: inline-block; width: 50px;"></span>
                            </div>
                            <div>
                               
                                <span class="small-underline">
                                    @if ($data->country !== 'Philippines')
                                         x
                                    @endif
                                </span>
                                <span style="padding-right: 29px; font-size: 12px">Abroad (Specify)</span>    
                                <span style="border-bottom: 1px solid #000; display: inline-block; width: 50px;"></span>
                            </div>
                          
                            <br>

                            <label class="rigthside-font">In case of Sick Leave:</label>
                            <div>
                                <span class="small-underline">
                                    @if ($data->is_outpatient === false)
                                        x
                                    @endif
                                </span>
                                <span style="padding-right: 4px; font-size: 12px">In Hospital (Specify Illness)</span>    
                                <span style="border-bottom: 1px solid #000; display: inline-block; width: 50px;">
                                    @if ($data->is_outpatient === false)
                                        {{ $data->illness }}
                                    @endif
                                </span>
                            </div>
                                    
                            <div>
                                <span class="small-underline">
                                    @if ($data->is_outpatient === true)
                                        x
                                    @endif
                                </span>
                                <span style="padding-right: 2px; font-size: 12px">Out Patient (Specify Illness)</span>
                                <span style="border-bottom: 1px solid #000; display: inline-block; width: 50px;">
                                    @if ($data->is_outpatient === true)
                                        {{ $data->illness }}
                                    @endif
                                </span>
                            </div>
                                  
                             

                            <hr style="margin-top: 10px; margin-bottom: 10px; border: 0; border-top: 1px solid black;">

                            <label class="rigthside-font">In case of Special Leave Benefits for Women:</label>

                            <div>
                                <span style="font-size: 12px">(Specify Illness)</span>
                                <span style="padding-top:5px;border-bottom: 1px solid #000; display: inline-block; width: 135px;">
                                    {{ $data->illness }}
                                </span>
                            </div>  

                            <hr  style="margin-top: 10px; margin-bottom: 10px; border: 0; border-top: 2px solid black;">

                            <label class="rigthside-font">In case of Study Leave:</label>

                            <div>
                                <span class="small-underline">
                                    @if ($data->is_masters === true)
                                        x
                                    @endif
                                </span>
                                <span style="padding-right: 2px; font-size: 12px">Completion of Master's Degree</span>
                            </div>
                            <div>
                                <span class="small-underline">
                                    @if ($data->is_board === true)
                                        x
                                    @endif
                                </span>
                                <span style="padding-right: 2px; font-size: 12px">BAR/Board Examination Review</span>
                            </div>

                            <label class="rigthside-font" style="margin-top:2px">Other Purpose:</label>
                            
                            <div>
                                <span class="small-underline"></span>
                                <span style="padding-right: 2px; font-size: 12px">Monetization of Leave Credits</span>
                            </div>

                            <div>
                                <span class="small-underline"></span>
                                <span style="padding-right: 2px; font-size: 12px">Terminal Leave</span>
                            </div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td colspan="7">
                        <table style="width: 100%; border: 0; border-collapse: collapse; ">
                            <tr style="border: 0">
                                <td class="topleft" colspan="4" style="border: 0 ;border-right: 1px solid #000"> 6. C.) NUMBER OF WORKING DAY APPLIED FOR:
                                    <div>
                                        <div class="text-center">
                                            <span style="font-size: 12px; font-weight:lighter; border-bottom: 1px solid #000; display: inline-block; width: 300px;">
                                                {{ number_format($data->applied_credits, 1) }} Day(s)
                                            </span>
                                         
                                        </div>
                
                                        <p style="margin-left:30px; margin-top:5px ">Inclusive Dates</p> 
                                        <div class="text-center">
                                            <span style="font-size: 12px; font-weight:lighter; border-bottom: 1px solid #000; display: inline-block; width: 300px; line-height: 6px">
                                                @if ($data->date_from === $data->date_to)
                                                    {{ date(' F d, Y', strtotime($data->date_from)) }}
                                                @else
                                                    {{ date(' F d, Y', strtotime($data->date_from)) }} - {{ date(' F d, Y', strtotime($data->date_to)) }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    
            
                                </td>
            
                                <td class="topleft" colspan="4" style=" border:0"> 6. D.) COMMUTATION
                                    <div class="mb-3 text-center" style=" margin-top: 5px">
                                        <div class="form-check form-check-inline">
                                          
                                                <label>( &nbsp;&nbsp; ) Requested</label>
                                           
                                        </div>
            
                                        <div class="form-check form-check-inline">
                                            <label> ( x ) Not Requested</label>
                                       
                                        </div>
            
                                        <div style="margin-top: 20px;">
                                            <span style="font-size: 13px; border-bottom: 1px solid #000; display: inline-block; width: 300px; text-transform:uppercase">
                                                {{ $data->employeeProfile->personalInformation->first_name }}
                                                {{ substr($data->employeeProfile->personalInformation->middle_name, 0, 1) }}
                                                {{ $data->employeeProfile->personalInformation->last_name }} 
                                            </span>
                                            <br> 
                                            <label style="text-align: center; display: block;">Signature of Applicant</label>
                                        </div>
                                    </div>
                                </td>

                            </tr>
                        </table>
                    </td>
                   
                </tr>

                <tr>
                    <td colspan="7" style="font-size: 15px"><small><b> DETAILS OF APPLICATION </b></small></td>
                 </tr>

                <tr>
                    <td colspan="7">
                        <table style="width: 100%; border: 0; border-collapse: collapse; ">
                            <td class="topleft" colspan="4" style="border: 0; border-right: 1px solid #000;"> 7. A) CERTIFICATION OF LEAVE CREDITS
                                <div class="mb-3" style="margin-top: 5px">
                                    <label style="padding-left: 40px">As of</label>
                                    <span style="font-size: 13px; border-bottom: 1px solid #000; display: inline-block; font-weight:lighter"> 
                                        {{ \Carbon\Carbon::now()->format('F d, Y')}}
                                    </span>
        
                                    <table class="small-table">
                                        <thead>
                                          <tr>
                                            <th scope="col">Vacation</th>
                                            <th scope="col">Sick</th>
                                            <th scope="col">TOTAL</th>
                                          </tr>
                                        </thead>
                                        <tbody>
                                          <tr>
                                            <td style="padding: 8px; font-size:12px"></td>
                                            <td style="padding: 8px; font-size:12px"></td>
                                            <td style="padding: 8px; font-size:12px"></td>
                                          </tr>
                                          <tr>
                                            <td style="padding: 8px; font-size:12px"></td>
                                            <td style="padding: 8px; font-size:12px"></td>
                                            <td style="padding: 8px; font-size:12px"></td>
                                          </tr>
                                          <tr>
                                            <td class="text-end">DAYS</td>
                                            <td class="text-end">DAYS</td>
                                            <td class="text-end">DAYS</td>
                                          </tr>
                                        </tbody>
                                    </table>
        
                                    <div class="text-center" style="padding-top: 20px;">
                                        <span style="font-size: 13px; border-bottom: 1px solid #000; display: inline-block; width: 300px;">
                                            {{ $hrmo_officer->supervisor->personalInformation->first_name }}
                                            {{ substr($hrmo_officer->supervisor->personalInformation->middle_name, 0, 1) }}
                                            {{ $hrmo_officer->supervisor->personalInformation->last_name }} 
                                        </span>
                                        <br> 
                                        <label style="font-weight:lighter; font-size:12px; text-align: center; display: block;">
                                            Supervising Administrative Officer-HRMO
                                        </label>
                                    </div>
                                </div>
                            </td>
        
                            <td class="topleft" colspan="4" style=" border:0"> 7. B) RECOMMENDATION
                                <div class="mb-3" style="margin-top: 5px">
                                    <div>
                                        <div class="form-check form-check-inline">
                                            @if ($data->status === 'for approving officer')
                                                <label> ( x ) Approved </label>
                                            @else
                                                <label> ( &nbsp;&nbsp; ) Approved </label>
                                            @endif
                                           
                                        </div>
                                        <br>
                                        <div class="form-check form-check-inline">
                                            @if ($data->status === 'declined by recommending officer')
                                                <label> ( x ) Disapproval due to </label>
                                            @else
                                                <label> ( &nbsp;&nbsp; ) Disapproval due to </label>
                                            @endif
                                            <br>
                                            
                                        </div>
                                        <div class="text-center">
                                            <span style="font-size 12px;  padding-top:20px; border-bottom: 1px solid #000; display: inline-block; width: 300px;">
                                                {{ $data->remarks }}
                                            </span>
                                        </div>
                                    </div>
        
                                    <div style="" class="text-center">
                                        <span style="font-size: 13px; padding-top:40px; border-bottom: 1px solid #000; display: inline-block; width: 300px;">
                                            {{ $data->recommendingOfficer->personalInformation->first_name }}
                                            {{ substr($data->recommendingOfficer->personalInformation->middle_name, 0, 1) }}
                                            {{ $data->recommendingOfficer->personalInformation->last_name }} 
                                        </span>
                                        <br> 
                                        <label style="display: block; font-weight:lighter; font-size:12px;">Unit/Section/Department Head</label>
                                        <label style="display: block; font-weight:lighter; font-size:12px;">(Signature over printed name)</label>
                                    </div>
                                </div>
                            </td>
                        </table>
                    </td>
                    
                </tr>

                <tr style="border-color: #ffffff">
                    <td class="topleft" colspan="3" style="border-right: #ddd; border-bottom: #ddd">
                        <label> 7. C) APPROVED FOR </label>
                        <div style="padding-top: 3px; padding-left: 20px; margin-top: 5px">
                            <span class="small-underline" style="font-size:12px;font-weight:lighter;">
                                @if ($data->without_pay === 0 ||$data->without_pay === false)
                                    {{ $data->applied_credits }}
                                @endif
                            </span>
                            <span style="padding-right: 20px">Days with pay</span>    
                            <span style="font-size: 12px;font-weight:lighter; border-bottom: 1px solid #000; display: inline-block; width: 100px;">
                                @if ($data->without_pay === 0 ||$data->without_pay === false)
                                    @if ($data->date_from === $data->date_to)
                                        {{ date(' F d, Y', strtotime($data->date_from)) }}
                                    @else
                                        {{ date(' F d, Y', strtotime($data->date_from)) }} - {{ date(' F d, Y', strtotime($data->date_to)) }}
                                    @endif
                                @endif
                            </span>
                            <br>                                        
                            <span class="small-underline" style="font-weight:lighter;">
                                @if ($data->without_pay === 1 ||$data->without_pay === true)
                                    {{ $data->applied_credits }}
                                @endif
                            </span>
                            <span style="padding-right: 5px">Days without pay</span>    
                            <span style="font-weight:lighter; border-bottom: 1px solid #000; display: inline-block; width: 100px;">
                                @if ($data->without_pay === 1 ||$data->without_pay === true)
                                    @if ($data->date_from === $data->date_to)
                                        {{ date(' F d, Y', strtotime($data->date_from)) }}
                                    @else
                                        {{ date(' F d, Y', strtotime($data->date_from)) }} - {{ date(' F d, Y', strtotime($data->date_to)) }}
                                    @endif
                                @endif
                            </span>    
                            <br>
                            <span class="small-underline"></span>
                            <span style="padding-right: 10px">Others (Specify)</span>    
                            <span style="border-bottom: 1px solid #000; display: inline-block; width: 100px;"></span>    
                        </div>
                    </td>
                    
                    <td class="topleft" colspan="4" style="border-left: #ddd; border-bottom: #ddd">
                        <label> 7. D) DISAPPROVED DUE TO: </label>
                        <div class="text-center" style="padding-top: 15px; margin-top: 5px">
                            <span></span>
                            <span style="border-bottom: 1px solid #000; display: inline-block; width: 200px;"> {{$data->remarks}} </span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td colspan="7" style="border-top: #ddd">
                        <h6 class="topleft" style="padding-top: 5px; padding-left: 20px">BY AUTHORITY OF THE SECRETARY OF HEALTH</h6>
                        <span style="font-size: 13px; padding: 10px; border-bottom: 1px solid #000; display: inline-block; width: 250px;"></span>
                        <br> 
                        <span style="font-size:12px"><b> Signature </b></span>
                        <br>
                        <span style="font-size: 13px; border-bottom: 1px solid #000; display: inline-block; width: 250px; padding-top: 30px">
                            <b>
                                {{ $data->approvingOfficer->personalInformation->first_name }}
                                {{ substr($data->approvingOfficer->personalInformation->middle_name, 0, 1) }}
                                {{ $data->approvingOfficer->personalInformation->last_name }}         
                            </b>
                        </span>
                        <br>
                        <span style="font-size:12px; font-weight:lighter">{{ $data->approvingOfficer->findDesignation()['name']  }}</span>
                        <br>
                        <h6 class="topleft" style="padding-top: 20px; padding-left: 20px">Date
                            <span style="border-bottom: 1px solid #000; display: inline-block; width: 250px;"></span>
                        </h6>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="row">
            <div class="col-12">
                <span style="display: block; font-size: 8px;">Adopted from CSC FORM NO. 6 REVISED 1998</span>  
            </div>

            <div class="col-10">
                <span class="text-center" style="display: block; font-size: 8px;">Rev.1</span>
            </div>

            <div class="col-2">
                <span class="text-end" style="display: block; font-size: 8px; padding-right:90px;">Effectivity Date: June 1, 2021</span>
            </div>

            <div class="col-12">
                <span style="display: block; font-size: 8px;">ZCMC-F-HRMO-02(B)</span>  

            </div>
        </div>
        
        <br>

        <h5 class="text-center">INSTRUCTIONS AND REQUIREMENTS</h5>
        <div class="row" style="width: 90%;">
            <div class="col-6">
                <p class="small-p">Application for any type of leave shall be made on this Form and to be
                    accomplished at least in duplicate with documentary requirements, as follows:
                </p>
                
                <p class="small-p">
                    1. Vacation leave* <br>
                    It shall be filed five (5) days in advance, whenever possible, of the effective
                    date of such leave. Vacation leave within the Philippines or abroad shall be
                    indicated in the form for purposes of securing travel authority and completing
                    clearance from money and work acountabilities.
                </p>

                <p class="small-p">
                    2. Mandatory/Forced leave <br>
                    Annual five-day vacation leave shall be forfeited if not taken during the year. In
                    case the scheduled leave has been cancelled in the exigency of the service by
                    the head of agency, it shall no longer be deducted from the accumulated
                    vacation leave. Availment of one (1) day or more Vacation Leave (VL) shall be
                    considered for complying the mandatory/forced leave subject to the conditions
                    under Section 25, Rule XVI of the Omnibus Rules Implementing E.O. No. 292.
                </p>

                <p class="small-p">
                    3. Sick leave* <br>
                    • It shall be filed immediately upon employee's return from such leave.
                    • If filed in advance or exceeding five (5) days, application shall be
                    accompanied by a medical certificate. In case medical consultation was not
                    availed of, an affidavit should be executed by an applicant.
                </p>
                
                <p class="small-p">
                    4. Maternity leave* – 105 days <br>
                    • Proof of pregnancy e.g. ultrasound, doctor’s certificate on the expected date
                    of delivery <br>
                    • Accomplished Notice of Allocation of Maternity Leave Credits (CS Form No.
                    6a), if needed <br>
                    • Seconded female employees shall enjoy maternity leave with full pay in the
                    recipient agency.
                </p>
                
                <p class="small-p">
                    5. Paternity leave – 7 days <br>
                    Proof of child’s delivery e.g. birth certificate, medical certificate and marriage
                    contract
                </p>
                
                <p class="small-p">
                    6. Special Privilege leave – 3 days <br>
                    It shall be filed/approved for at least one (1) week prior to availment, except on
                    emergency cases. Special privilege leave within the Philippines or abroad
                    shall be indicated in the form for purposes of securing travel authority and
                    completing clearance from money and work accountabilities.
                </p>
                
                <p class="small-p">
                    7. Solo Parent leave – 7 days <br>
                    It shall be filed in advance or whenever possible five (5) days before going on
                    such leave with updated Solo Parent Identification Card.
                </p>
                
                <p class="small-p">
                    8. Study leave* – up to 6 months <br>
                    • Shall meet the agency’s internal requirements, if any; <br>
                    • Contract between the agency head or authorized representative and the
                    employee concerned.
                </p>
                
                <p class="small-p">
                    9. VAWC leave – 10 days <br>
                    • It shall be filed in advance or immediately upon the woman employee’s
                    return from such leave. <br>
                    • It shall be accompanied by any of the following supporting documents: <br>

                    a. Barangay Protection Order (BPO) obtained from the barangay; <br>

                    b. Temporary/Permanent Protection Order (TPO/PPO) obtained from the court; <br>

                    c. If the protection order is not yet issued by the barangay or the
                    court, a certification issued by the Punong Barangay/Kagawad or Prosecutor
                    or the Clerk of Court that the application for the BPO, TPO or PPO has been 
                    filed with the said office shall be sufficient to support the application for the ten-
                    day leave; or <br>

                    d. In the absence of the BPO/TPO/PPO or the certification, a police
                    report specifying the details of the occurrence of violence on the victim and a
                    medical certificate may be considered, at the discretion of the immediate
                    supervisor of the woman employee concerned.
                </p>
            </div>
            
            
            <div class="col-6">
                <p class="small-p">
                    10. Rehabilitation leave* – up to 6 months <br>
                    • Application shall be made within one (1) week from the time of the accident
                    except when a longer period is warranted. <br>
                    • Letter request supported by relevant reports such as the police report, if
                    any, <br>
                    • Medical certificate on the nature of the injuries, the course of treatment
                    involved, and the need to undergo rest, recuperation, and rehabilitation, as
                    the case may be. <br>
                    • Written concurrence of a government physician should be obtained relative
                    to the recommendation for rehabilitation if the attending physician is a private
                    practitioner, particularly on the duration of the period of rehabilitation.
                </p>
                
                <p class="small-p">
                    11. Special leave benefits for women* – up to 2 months <br>
                    • The application may be filed in advance, that is, at least five (5) days prior to
                    the scheduled date of the gynecological surgery that will be undergone by the
                    employee. In case of emergency, the application for special leave shall be
                    filed immediately upon employee’s return but during confinement the agency
                    shall be notified of said surgery. <br>
                    • The application shall be accompanied by a medical certificate filled out by
                    the proper medical authorities, e.g. the attending surgeon accompanied by a
                    clinical summary reflecting the gynecological disorder which shall be
                    addressed or was addressed by the said surgery; the histopathological
                    report; the operative technique used for the surgery; the duration of the
                    surgery including the peri-operative period (period of confinement around
                    surgery); as well as the employees estimated period of recuperation for the
                    same.
                </p>

                <p class="small-p">
                    12. Special Emergency (Calamity) leave – up to 5 days <br>
                    • The special emergency leave can be applied for a maximum of five (5)
                    straight working days or staggered basis within thirty (30) days from the
                    actual occurrence of the natural calamity/disaster. Said privilege shall be
                    enjoyed once a year, not in every instance of calamity or disaster. <br>
                    • The head of office shall take full responsibility for the grant of special
                    emergency leave and verification of the employee’s eligibility to be granted
                    thereof. Said verification shall include: validation of place of residence based
                    on latest available records of the affected employee; verification that the
                    place of residence is covered in the declaration of calamity area by the proper
                    government agency; and such other proofs as may be necessary.
                </p>

                <p class="small-p">
                    13. Monetization of leave credits <br>
                    Application for monetization of fifty percent (50%) or more of the accumulated
                    leave credits shall be accompanied by letter request to the head of the
                    agency stating the valid and justifiable reasons.
                </p>
                
                <p class="small-p">
                    14. Terminal leave* <br>
                    Proof of employee’s resignation or retirement or separation from the service.
                </p>
                
                <p class="small-p">
                    15. Adoption Leave <br>
                    • Application for adoption leave shall be filed with an authenticated copy of
                    the Pre-Adoptive Placement Authority issued by the Department of Social
                    Welfare and Development (DSWD).
                </p>
            </div>

            <span style="border-bottom: 2px solid #000; display: inline-block; width: 8.5in;"></span>

            <div class="col-12">
                <p class="small-p">
                    * For leave of absence for thirty (30) calendar days or more and terminal leave, application shall be accompanied by a clearance from money, property and
                    work-related accountabilities (pursuant to CSC Memorandum Circular No. 2, s. 1985).
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>

