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

        .topleft {
            vertical-align: top;
            text-align: left;"
        }

        .small {
            font-size: 12px;
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
                    <td class="topleft" colspan="1" style="width: 35%"> 1. OFFICE/AGENCY
                        <div class="mb-3 text-center">
                            <label class="form-check-label" for="inlineCheckbox1">
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
                                Last Name
                            </div>

                            <div class="col">
                                (First) <br>
                                First Name
                            </div>

                            <div class="col">
                                (Middle) <br>
                                Middle Name
                            </div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="topleft" colspan="1"> 3. DATE OF FILING
                        <div class="mb-3 text-center">
                            <label class="form-check-label" for="inlineCheckbox1">
                                December 29, 2023
                            </label>
                        </div>
                    </td>

                    <td class="topleft" colspan="3" style="width: 33%"> 4. POSITION
                        <div class="mb-3 text-center">
                            <label class="form-check-label" for="inlineCheckbox1">
                                Nursing Attendant II
                            </label>
                        </div>
                    </td>

                    <td class="topleft" colspan="2"> 5. SALARY
                        <div class="mb-3 text-center">
                            <label class="form-check-label" for="inlineCheckbox1">

                            </label>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td colspan="6"> DETAILS OF APPLICATION </td>
                </tr>

                <tr>
                    <td class="topleft" colspan="4"> 6. A.) TYPE OF LEAVE TO BE AVAILED OF
                        <div class="mb-3 text-start">
                            <div> ( )
                                <label class="form-check-label small" for="inlineCheckbox1">
                                    Ex. Vacation Leave (Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292)
                                </label>
                            </div>
                    </td>
                    
                    <td class="topleft" colspan="1"> 6. B.) DETAILS OF LEAVE <br>
                        <div class="mb-3 text-start small">
                            <label class="form-check-label">
                                <small><strong> In case of Vacation/Special Privilege Leave: </strong></small>
                            
                            <ul class="list-unstyled">
                                <li>__ Within the Philippines ________</li>
                                <li>__ Abroad (Specify) ________</li>
                              </ul>
                            </label>
                            
                            <br>

                            <label class="form-check-label">
                                <small><strong> In case of Sick Leave: </strong></small>

                            <ul class="list-unstyled">
                                <li>__ In Hospital (Specify Illness) ________</li>
                                <li>__ Out Patient (Specify Illness) ________</li>
                              </ul>
                            </label>

                            <hr style="">

                            <label class="form-check-label">
                                <small><strong> In case of Special Leave Benefits for Women: </strong></small>
                            
                                <ul class="list-unstyled">
                                    <li>(Specify Illness) ________</li>
                                    <li>_____________________</li>
                                </ul>
                            </label> 

                            <br>

                            <label class="form-check-label">
                                <small><strong> In case of Study Leave: </strong></small>
                            
                                <ul class="list-unstyled">
                                    <li>__ Completion of Master's Degree</li>
                                    <li>__ BAR/Board Examination Review</li>
                                </ul>
                            </label>

                            <br>

                            <label class="form-check-label">
                                <small><strong> Other Purpose:  </strong></small>
                            
                                <ul class="list-unstyled">
                                    <li>__ Monetization of Leave Credits</li>
                                    <li>__ Terminal Leave</li>
                                </ul>
                            </label>
                        </div>
                    </td>
                </tr>

                <tr>
                    <small> <td class="topleft" colspan="2" style="width: 50%"> 6. C.) NUMBER OF WORKING DAY APPLIED FOR:
                        <p class="text-center">_________________________________</p>

                        <div class="text-center">
                            Inclusive Dates
                        </div>

                        <p class="text-center">_________________________________</p>
                    </td>

                    <td class="topleft" colspan="4"> 6. D.) COMMUTATION
                        <div class="mb-3 text-center">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox1" value="option1">
                                <label class="form-check-label" for="inlineCheckbox1">Requested</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox2" value="option2">
                                <label class="form-check-label" for="inlineCheckbox2">Not Requested</label>
                            </div>

                            <p class="text-center text-underline"> <br> <u> ADRIAN A. AGCAOLI </u> <br> Signature of Applicant</p>
                        </div>
                    </td>
                    </small>
                </tr>

                <tr>
                    <td colspan="6">DETAILS OF ACTION ON APPLICATION</td>
                 </tr>

                 <tr>
                    <td class="topleft" colspan="3"> 7. A) CERTIFICATION OF LEAVE CREDITS
                        <div class="mb-3">
                            
                        </div>
                    </td>

                    <td class="topleft" colspan="3"> 7. B) RECOMMENDATION
                        <div class="mb-3">
                           
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

