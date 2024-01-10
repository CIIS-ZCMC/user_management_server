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
            font-size: 10px;
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
            font-size: 10px;
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
                                    <span>Within the Philippines</span>    
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 84px;"></span>
                                </li>
                                
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 25px">Abroad (Specify)</span>    
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 84px;"></span>
                                </li>
                              </ul>
                            
                            <label class="rigthside-font">In case of Sick Leave:</label>
                            <ul class="list-unstyled">
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 4px">In Hospital (Specify Illness)</span>    
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 55px;"></span>
                                </li>
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 2px">Out Patient (Specify Illness)</span>
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 55px;"></span>
                                </li>
                              </ul>

                            <hr>

                            <label class="rigthside-font">In case of Special Leave Benefits for Women:</label>
                            <ul class="list-unstyled">
                                <li>
                                    <span>(Specify Illness)</span>
                                    <span style="border-bottom: 1px solid #000; display: inline-block; width: 135px;"></span>
                                </li>
                            </ul>

                            <hr>

                            <label class="rigthside-font">In case of Study Leave:</label>
                            <ul class="list-unstyled">
                                <li> 
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 2px">Completion of Master's Degree</span>
                                </li>
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 2px">BAR/Board Examination Review</span>
                                </li>
                            </ul>

                            <label class="rigthside-font">Other Purpose:</label>
                            <ul class="list-unstyled">
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 2px">Monetization of Leave Credits</span>
                                </li>
                                <li>
                                    <span class="small-underline">√</span>
                                    <span style="padding-right: 2px">Terminal Leave</span>
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

                            <div style="padding-top: 64px;" class="text-center">
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
                            <h6 class="text-start">RECOMMENDING APPROVAL :</h6> <br>
                            <h6 class="text-decoration-offset" style="margin-bottom: 0%">Juanita J. Juanito</h6>
                            <small>Finance and Management Officer II</small>
                        </div>
                    </td>
                 </tr>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>

