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
            /* font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0; */
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            width: 8.5in; /* Width of a standard long bond paper in inches */
            height: 14in; /* Height of a standard long bond paper in inches */
            margin-left: auto;
            margin-right: auto;
            /* display: flex; */
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
    </style>
</head>
<body>
    <div>
        <span style="display: block; font-size: 8px;">Civil Service Form No. 6</span>  
        <span style="display: block; font-size: 8px;">Revised 2020</span>
    </div>

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
            <small> <b> APPLICATION FOR LEAVE </b> </small>
        </div>

        <table class="table-bordered" border="1" cellspacing="0" cellpadding="10">
            <tbody>
                <tr>
                    <td class="topleft" colspan="1" style="width: 35%;"> 1. OFFICE/AGENCY
                        <div class="mb-3 topcenter">
                            <label>
                                 Zamboanga City Medical Center
                            </label>
                        </div>
                    </td>

                    <td class="text-start topleft" colspan="5"> 
                        <div class="row align-items-start">
                            <div class="col">
                                2. NAME :
                            </div>

                            <div class="col">
                                (Last) <br>
                                <span class="topcenter"> Last Name </span>
                            </div>

                            <div class="col">
                                (First) <br>
                                <span class="topcenter"> First Name </span>
                            </div>

                            <div class="col">
                                (Middle) <br>
                                <span class="topcenter"> Middle Name </span>
                            </div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="topleft" colspan="1"> 3. DATE OF FILING
                        <div class="mb-3 topcenter">
                            <label>
                                December 29, 2023
                            </label>
                        </div>
                    </td>

                    <td class="topleft" colspan="3" style="width: 33%"> 4. POSITION
                        <div class="mb-3 topcenter">
                            <label>
                                Nursing Attendant II
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
                    <td colspan="6" ><small><b> DETAILS OF APPLICATION </b></small></td>
                </tr>

                <tr>
                    <td class="topleft" colspan="4"> 6. A.) TYPE OF LEAVE TO BE AVAILED OF
                        <div class="mb-3 text-start">
                            <div> ( )
                                <label class="small">
                                    Ex. Vacation Leave (Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292)
                                </label>
                            </div>
                    </td>
                    
                    <td class="topleft" colspan="1"> 6. B.) DETAILS OF LEAVE <br>
                        <div class="mb-3 text-start small">
                            <label class="rigthside-font">In case of Vacation/Special Privilege Leave:</label>
                            <ul class="list-unstyled">
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="font-size: 12px;">Within the Philippines</span>    
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 84px;"></span>
                                </li>
                                
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 29px; font-size: 12px">Abroad (Specify)</span>    
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 84px;"></span>
                                </li>
                              </ul>
                            
                            <label class="rigthside-font">In case of Sick Leave:</label>
                            <ul class="list-unstyled">
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 4px; font-size: 12px">In Hospital (Specify Illness)</span>    
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 55px;"></span>
                                </li>
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 2px; font-size: 12px">Out Patient (Specify Illness)</span>
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 55px;"></span>
                                </li>
                              </ul>

                            <hr>

                            <label class="rigthside-font">In case of Special Leave Benefits for Women:</label>
                            <ul class="list-unstyled">
                                <li>
                                    <span style="font-size: 12px">(Specify Illness)</span>
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 135px;"></span>
                                </li>
                            </ul>

                            <hr>

                            <label class="rigthside-font">In case of Study Leave:</label>
                            <ul class="list-unstyled">
                                <li> 
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 2px; font-size: 12px">Completion of Master's Degree</span>
                                </li>
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 2px; font-size: 12px">BAR/Board Examination Review</span>
                                </li>
                            </ul>

                            <label class="rigthside-font">Other Purpose:</label>
                            <ul class="list-unstyled">
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 2px; font-size: 12px">Monetization of Leave Credits</span>
                                </li>
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 2px; font-size: 12px">Terminal Leave</span>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>

                <tr>
                    <small> <td class="topleft" colspan="2" style="width: 50%"> 6. C.) NUMBER OF WORKING DAY APPLIED FOR:
                        <p class="text-center">
                            <span style="border-bottom: 1px solid #000; display: inline-block; width: 300px;"></span>
                        </p>

                        <div style="padding-left: 40px">
                            Inclusive Dates
                        </div>
                        <p class="text-center">
                            <span style="border-bottom: 1px solid #000; display: inline-block; width: 300px;"></span>
                        </p>

                    </td>

                    <td class="topleft" colspan="4"> 6. D.) COMMUTATION
                        <div class="mb-3 text-center">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox1" value="option1">
                                <label>Requested</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox2" value="option2">
                                <label for="inlineCheckbox2">Not Requested</label>
                            </div>

                            <div style="margin-top: 20px;">
                                <span style="border-bottom: 1px solid #000; display: inline-block; width: 250px;"></span>
                                <br> 
                                <label style="text-align: center; display: block;">Signature of Applicant</label>
                            </div>
                        </div>
                    </td>
                    </small>
                </tr>

                <tr>
                    <td colspan="6" ><small><b> DETAILS OF APPLICATION </b></small></td>
                 </tr>

                 <tr>
                    <td class="topleft" colspan="3"> 7. A) CERTIFICATION OF LEAVE CREDITS
                        <div class="mb-3">
                            <label style="padding-left: 40px">As of</label>
                            <span style="border-bottom: 1px solid #000; display: inline-block; width: 100px;"></span>

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
                                    <td>D</td>
                                    <td>D</td>
                                    <td>D</td>
                                  </tr>
                                  <tr>
                                    <td>D</td>
                                    <td>D</td>
                                    <td>D</td>
                                  </tr>
                                  <tr>
                                    <td class="text-end">DAYS</td>
                                    <td class="text-end">DAYS</td>
                                    <td class="text-end">DAYS</td>
                                  </tr>
                                </tbody>
                            </table>

                            <div class="text-center" style="padding-top: 20px;">
                                <span style="border-bottom: 1px solid #000; display: inline-block; width: 250px;"></span>
                                <br> 
                                <label style="text-align: center; display: block;">Supervising Administrative Officer-HRMO</label>
                            </div>
                        </div>
                    </td>

                    <td class="topleft" colspan="3"> 7. B) RECOMMENDATION
                        <div class="mb-3">
                            <div style="padding-left: 40px">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="inlineCheckbox1">
                                    <label class="form-check-label" for="defaultCheck1"> Approved </label>
                                </div>
                                <br>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="inlineCheckbox2">
                                    <label class="form-check-label" for="defaultCheck2"> Disapproval due to</label>
                                    <br>
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 220px;"></span>
                                </div>
                            </div>

                            <div style="padding-top: 55px;" class="text-center">
                                <span style="border-bottom: 1px solid #000; display: inline-block; width: 220px;"></span>
                                <br> 
                                <label style="display: block;">Unit/Section/Department Head</label>
                                <label style="display: block;">(Signature over printed name)</label>
                            </div>
                        </div>
                    </td>
                 </tr>

                 <tr>
                    <td colspan="6">
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6 topleft"> 7. C) APPROVED FOR
                                    <div class="text-center">
                                        <span class="small-underline">√</span>
                                        <span style="padding-right: 20px">Days with pay</span>    
                                        <span style="border-bottom: 1px solid #000; display: inline-block; width: 84px;"></span>
                                        <br>                                        
                                        <span class="small-underline">√</span>
                                        <span style="padding-right: 5px">Days without pay</span>    
                                        <span style="border-bottom: 1px solid #000; display: inline-block; width: 84px;"></span>    
                                        <br>
                                        <span class="small-underline">√</span>
                                        <span style="padding-right: 10px">Others (Specify)</span>    
                                        <span style="border-bottom: 1px solid #000; display: inline-block; width: 84px;"></span>    
                                    </div>
                                </div>

                                <div class="col-6 topleft">7. C) APPROVED FOR
                                    <div class="text-center">
                                        <br>
                                        <span style="border-bottom: 1px solid #000; display: inline-block; width: 200px;"></span>
                                        <br>
                                        <span style="border-bottom: 1px solid #000; display: inline-block; width: 200px;"></span>    
                                    </div>
                                </div>
                            
                                <div class="col-12"><br>
                                    <h6 class="topleft" style="padding-top: 5px; padding-left: 20px">BY AUTHORITY OF THE SECRETARY OF HEALTH</h6>
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 250px;"></span>
                                    <br> 
                                    <span><b> Signature </b></span>
                                    <br>
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 250px; padding-top: 30px">
                                        <b>VIOLETA C. MAGASO</b>
                                    </span>
                                    <br>
                                    <span>Chief Administrative Officer</span>
                                    <br>
                                    <h6 class="topleft" style="padding-top: 20px; padding-left: 20px">Date
                                        <span style="border-bottom: 1px solid #000; display: inline-block; width: 250px;"></span>
                                    </h6>
                                </div>
                            </div>
                        </div>
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
                <span class="text-center" style="display: block; font-size: 8px;">Effectivity Date: June 1, 2021</span>
            </div>

            <div class="col-12">
                <span style="display: block; font-size: 8px;">ZCMC-F-HRMO-02(B)</span>  

            </div>
        </div>
        
        <br>

        <h5 class="text-center">INSTRUCTIONS AND REQUIREMENTS</h5>
        <div class="row">
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

