<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Data Sheet</title>
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous"> --}}

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
        @font-face {
            font-family:'Arial Narrow'; 
            src: url('/fonts/ArialNarrow.woff') format('woff'),
                url('/fonts/ArialNarrow.ttf') format('ttf');
            /* Add additional src lines for different font file formats if needed */
            /* Define other font properties like font-weight and font-style if needed */
        }

        body {
            font-family: 'Arial Narrow';
            margin: 0;
            padding: 0;
            max-width: 100vw
        }

        th, td {
            border-collapse: collapse;
        }
       
        .section-header {
            background-color: grey;
            color: white;
            padding: 3px 0px 3px 0px;
            font-size: 11px;
            margin: 0
        }

        td.title {
            text-transform: uppercase;
            font-family: 'Arial Narrow', sans-serif; 
            font-size: 8px;
            width: 20%;
            background-color: rgb(224, 224, 224);
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .bg-title {
            background-color: rgb(224, 224, 224);
        }

        .title {
            font-family: 'Arial Narrow', sans-serif; 
            font-size: 8px;
            padding-top: 5px;
            padding-bottom: 5px;
           
        }

        .sentence-narrow {
            font-family: 'Arial Narrow', sans-serif; 
            font-size: 8px;
        }

        .row-value {
            font-size: 8px;
            padding:  4px
        }

        .title-indent {
            padding-left: 10px;
        }

        label {
            font-family: 'Calibri', sans-serif;
            font-size: 8px;
        }
        .table-section {
            width: 100%; 
            margin: 0px
        }
        
        .bottom-label i {
            font-size: 6px;
            margin-top: 0
        }

        hr {
            margin: 0px
        }

        .address-layout {
            font-size: 9px; 
            position: relative;
        }

        .address-item {
            position: absolute;
            top: 0;
        }

        .footer-continue {
            color: red;
            font-weight: bold;
            text-align: center;
            font-style: italic;
            background-color: rgb(224, 224, 224);
        }

        /* PRE-DEFINED */
        .border {
            border: 1px solid;
        }
        .border-dark {
            border-color: black;
        }

        .text-center {
            text-align: center
        }

        .main-container {
            border: 1px solid black;
        }

        .fst-italic {
            font-style: italic
        }

        .fw-bold {
            font-weight: bold
        }

        .my-0 {
            margin-top: 0px;
            margin-bottom: 0px
        }

        .my-1 {
            margin-top: 10px;
            margin-bottom: 10px
        }

        .py-0 {
            padding-top: 0px;
            padding-bottom: 0px
        }

        .py-2 {
            padding-top: 8px;
            padding-bottom: 8px
        }

        .py-3 {
            padding-top: 12px;
            padding-bottom: 12px
        }

        .flex-center {
            display: flex;
            align-items: center
        }

        
    </style>
</head>
<body>  
    <div class="main-container">
        {{-- TOP CONTAINER --}}
        <table style="width: 100%" cellspacing="0" cellpadding="0">
            <tr  style="font-size: 9px; padding: 0 ">
                <td colspan="4">
                    <p class="fw-bold fst-italic my-0" style="font-family: Calibri, sans-serif">CS Form No. 212 <br/>Revised 2017</p>
                
                    <h2 class="fw-bolder text-center my-1" style="font-size: 22px">PERSONAL DATA SHEET</h2>

                    <p class="fw-bold fst-italic my-0" style="font-size: 8px">WARNING: Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administrative/criminal case/s against the person concerned. <br/> READ THE ATTACHED GUIDE TO FILLING OUT THE PERSONAL DATA SHEET (PDS) BEFORE ACCOMPLISHING THE PDS FORM.</p>
                    

            <tr style="font-family: 'Arial Narrow', sans-serif; font-size: 8px" class="border-bottom border-dark">
                <td style="font-family: 'Arial Narrow', sans-serif; font-size: 9px" style="width: 75%">
                    Print legibly. Tick appropriate boxes (<input type="checkbox" style="padding-top: 2px"/>) and use separate sheet if necessary. Indicate N/A if not applicable.  <b>DO NOT ABBREVIATE.</b>
                </td>
                <td class="border title py-0" style="width: 8%">1. CS ID No.</td>
                <td class="border py-0" style="text-align: right" style="width: 20%">(Do not fill up. For CSC use only)</td>
            </tr>
        </table>

        {{-- PERSONAL INFORMATION --}}
        <h5 class="section-header fst-italic" >
        I. PERSONAL INFORMATION
        </h5>

        {{-- NAME --}}
        <table cellspacing="0" cellpadding="0" class="border border-dark table-section"> 

            <tr padding="0">
                <td class="border-right border-dark title">2. Surname</td>
                <td colspan="2" class="border border-dark row-value">___data___</td>
            </tr>
            <tr>
                <td class="ml-3 title title-indent" >First name</td> 
                <td class="border border-dark row-value" style="width: 60%" >___data___</td> 
                <td class="border border-dark title"><div class="title" style="font-size: 6px">NAME EXTENSION (JR., SR) </div> <div class="row-value">___data___</div></td>
            </tr>

            <tr>
                <td class="border-right border-dark title title-indent">Middle name</td>
                <td colspan="2" class="border border-dark row-value">___data___</td>
            </tr>    
        </table>

        {{-- BIRTH DETAILS --}}
        <table cellspacing="0" cellpadding="0" class="border border-dark table-section"> 
            <tr>
                <td class="border border-dark title">3. Date of birth <br/> <div class="title-indent pb-2">(mm/dd/yyyy)</div></td>
                <td class="border border-dark row-value" >___data___</td>

                {{-- CITIZENSHIP --}}
                <td rowspan="3" class="border-right border-dark pt-0 title" style="width: 23%">
                
                    <div style="margin-top:0px"> 16. Citizenship</div>
                    <div class="sentence-narrow text-center mt-4 mb-3" style="text-transform: none !important">If holder of  dual citizenship, <br/> please indicate the details.</div>
                </td>
                <td rowspan="3" class="border border-dark py-0">
                    <div class="d-flex align-items-center gap-1 p-2">
                        <input type="checkbox" id="Filipino" name="Filipino" value="Filipino"> <label for="Filipino" style="margin-right: 30px">Filipino</label>
                        <input type="checkbox" id="Dual Citizenship" name="Dual Citizenship" value="Dual Citizenship"> <label for="Dual Citizenship">Dual Citizenship</label>
                    </div>


                    <div class="d-flex align-items-center gap-1" style="margin-left: 120px">
                        <input type="checkbox" id="by birth" name="by birth" value="by birth"> <label for="by birth" style="margin-right: 10px">by birth</label>
                        <input type="checkbox" id="by naturalization" name="by naturalization" value="by naturalization"> <label for="by naturalization">by naturalization</label>
                    </div>

                    <label style="margin-left: 120px">Pls. indicate country:</label>

                    {{-- COUNTRY --}}
                    <div class="row-value py-0">___data___</div>
                </td>
            </tr>
            <tr>
                <td class="title border border-dark">4. PLACE OF BIRTH</td> 
                <td class="border border-dark row-value">___data___</td> 
            </tr>

            <tr>
                <td class="title" style="width: 20%;" >5. Sex</td> 
                <td class="border border-dark row-value" style="width: 20%">
                    <div class="d-flex align-items-center gap-1 ">
                        <input type="checkbox" id="Male" name="Male" value="Male"> <label for="Male" style="margin-right: 60px">Male</label>
                        <input type="checkbox" id="Female" name="Female" value="Female"> <label for="Female">Female</label>
                    </div>
                </td> 
            </tr>
        </table>

        {{-- CIVIL STAT AND RESIDENTIAL--}}
        <table cellspacing="0" cellpadding="0" class="border border-dark table-section"> 
            <tr>
                <td class="border border-dark title pt-0" > <div style="margin-top: 0px">6. CIVIL STATUS</div></td>
                {{-- CIVIL STAT CHECKBOX --}}
                <td class="border border-dark row-value"  style="width: 20%;" >
                    <div class="flex-center ">
                       <div><input type="checkbox"> <label style="margin-right: 20px">Single</label></div> 
                        <div><input type="checkbox"> <label>Married</label></div>
                    </div>
                    <div class="flex-center">  
                        <input type="checkbox"> <label style="margin-right: 20px">Widowed</label>
                        <input type="checkbox"> <label>Separated</label>
                    </div>
                    
                    <input type="checkbox"> <label style="margin-right: 30px">Other/s:</label>
                </td>
                {{-- RESIDENTIAL ADDRESS --}}
                <td class="title" >
                    <div style="margin-top: 0px">17. RESIDENTIAL ADDRESS</div>
                </td>
            
                <td class="border border-dark" rowspan="2" >
                    {{-- STREET --}}

                    <div class="address-layout">
                        <div class="address-item">Address Line 1</div>
                        <div class="address-item">Address Line 2</div>
                    </div>
                   
                    <hr/>
                    <div class="address-layout bottom-label">
                        <i>House/Block/Lot No.</i>
                        <i>Street</i>
                        
                    </div>
                    <hr/>

                    {{-- STREET --}}
                    <div class="address-layout">
                        <span>address</span>
                        <span>address</span>
                    </div>
                    <hr/>
                    <div class="address-layout bottom-label">
                        <i>Subdivision/Village</i>
                        <i>Barangay</i>
                    </div>
                    <hr/>

                    {{-- CITY --}}
                    <div class="address-layout ">
                        <span>address</span>
                        <span>address</span>
                    </div>
                    <hr/>
                    <div class="address-layout bottom-label">
                        <i>City/Municipality</i>
                        <i>Province</i>
                    </div>
                </td>
            </tr>

            {{-- HEIGHT --}}
            <tr>
                <td class="border border-dark title" >7. HEIGHT (m)</td>
                <td class="border border-dark row-value">___data___</td>
                <td class="title" style="width: 17%">
            
                </td>
            </tr>

            {{-- WEIGHT --}}
            <tr>
        
                <td class="border border-dark title" >8. WEIGHT (kg)</td>
                <td class="border border-dark row-value">___data___</td>
                
                <td class="title text-center" >
                    ZIP CODE
                </td>
                <td class="border border-dark row-value" >
                -
                </td>

            </tr>

        </table>

        {{-- PERMANENT--}}
        <table cellspacing="0" cellpadding="0" class="border border-dark table-section"> 
            {{-- BLOOD TYPE --}}
            <tr>
                <td class="border border-dark title"> 9. Blood Type</td>
                {{-- CIVIL STAT CHECKBOX --}}
                <td class="border border-dark row-value" style="width: 20%" >
                -
                </td>
                {{-- PERMANENT ADDRESS --}}
                <td class="title" rowspan="3" >
                    <div style="margin-top: 0px">18. PERMANENT ADDRESS</div>
                </td>
            
                <td class="border border-dark" rowspan="3" >
                    {{-- STREET --}}
                    <div class="address-layout">
                        <span>___data___</span>
                        <span>___data___</span>
                    </div>
                    <hr/>
                    <div class="address-layout bottom-label">
                        <i>House/Block/Lot No.</i>
                        <i>Street</i>
                        
                    </div>
                    <hr/>

                    {{-- SUBDIVISION --}}
                    <div class="address-layout">
                        <span>___data___</span>
                        <span>___data___</span>
                    </div>
                    <hr/>
                    <div class="address-layout bottom-label">
                        <i>Subdivision/Village</i>
                        <i>Barangay</i>
                    </div>
                    <hr/>

                    {{-- CITY --}}
                    <div class="address-layout ">
                        <span>___data___</span>
                        <span>___data___</span>
                    </div>
                    <hr/>
                    <div class="address-layout bottom-label">
                        <i>City/Municipality</i>
                        <i>Province</i>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="border border-dark title"> 10. GSIS ID NO.</td>
                {{-- CIVIL STAT CHECKBOX --}}
                <td class="border border-dark row-value" >
                -
                </td>
                
            </tr>

            <tr>
                <td class="border border-dark title"> 11. PAG-IBIG ID NO.</td>
                {{-- CIVIL STAT CHECKBOX --}}
                <td class="border border-dark row-value" >
                -
                </td>
                
            </tr>

            <tr>
                <td class="border border-dark title"> 12. PHILHEALTH NO.</td>
                {{-- CIVIL STAT CHECKBOX --}}
                <td class="border border-dark row-value" >
                -
                </td>
                
                <td class="title text-center" >
                    ZIP CODE
                </td>
                <td class="border border-dark row-value" >
                    -
                </td>
            </tr>

        
        </table>

        {{-- IDs--}}
        <table cellspacing="0" cellpadding="0" class="border border-dark table-section"> 
            {{-- SSS --}}
            <tr>
                <td class="title"> 13. SSS NO.</td>
                
                <td class="border border-dark row-value" style="width: 20%">
                -
                </td>
            
                {{-- TELEPHONE --}}
                <td class="title border-top-0" >
                    19. TELEPHONE NO.
                </td>
            
                <td class="border border-dark row-value" >
                -
                </td>
            </tr>

            {{-- TIN --}}
            <tr>
                <td class="border border-dark title"> 14. TIN NO.</td>
                
                <td class="border border-dark row-value">
                -
                </td>
            
                {{-- MOBILE --}}
                <td class="title border border-dark" >
                    20. MOBILE NO.
                </td>
            
                <td class="border border-dark row-value" >
                -
                </td>
            </tr>

            {{-- EMPLOYEE NUMBER --}}
            <tr>
                <td class="border border-dark title">15. AGENCY EMPLOYEE NO.</td>
                
                <td class="border border-dark row-value">
                -
                </td>
            
                {{-- EMAIL --}}
                <td class="title border border-dark" >
                    21. E-MAIL ADDRESS (if any)
                </td>
            
                <td class="border border-dark row-value" >
                -
                </td>
            </tr>


        
        </table>

        {{-- FAMILY BACKGROUND --}}
        <h5 class="section-header fst-italic">
            II.  FAMILY BACKGROUND
        </h5>

        {{-- SPOUSE --}}
        <table cellspacing="0" cellpadding="0" class="border border-dark table-section"> 
            <tr >
                <td class="border-right border-dark title" style="width: 21%">22. SPOUSE'S SURNAME</td>
                <td class="border border-dark row-value" colspan="2" >___data___</td>
                <td class="border border-dark bg-title" style="width: 30%;font-size: 8px" >23. NAME of CHILDREN  (Write full name and list all)</td>
                <td class="border border-dark bg-title text-center" style="width: 12%; font-size: 8px">DATE OF BIRTH <br/>(mm/dd/yyyy) </td>
            </tr>
            <tr>    
                <td class="ml-3 title title-indent" > FIRST NAME</td>   
                <td class="border border-dark row-value"  style="width: 21%">___data___</td> 
                <td class="border border-dark bg-title">
                    <div style="font-size: 6px; margin-bottom: 0px">NAME EXTENSION (JR., SR) <div class="row-value">___data___</div> </div> 
                   
                </td>
                <td class="border border-dark row-value" >___data___</td> 
                <td class="border border-dark row-value" >___data___</td> 
            </tr>

            <tr>
                <td class="border-right border-dark title title-indent">Middle name</td>
                <td colspan="2" class="border border-dark row-value">___data___</td> 
                <td class="border border-dark row-value" >___data___</td>
                <td class="border border-dark row-value" >___data___</td>  
            </tr>  
            <tr >
                <td class="border border-dark title title-indent">OCCUPATION</td>
                <td class="border border-dark row-value" colspan="2">___data___</td> 
                <td class="border border-dark row-value" >___data___</td> 
                <td class="border border-dark row-value" >___data___</td> 
            </tr>
            <tr>    
                <td class="border border-dark title title-indent">EMPLOYER/BUSINESS NAME</td>
                <td class="border border-dark row-value" colspan="2">___data___</td> 
                <td class="border border-dark row-value" >___data___</td> 
                <td class="border border-dark row-value" >___data___</td> 
            </tr>

            <tr>
                <td class="border border-dark title title-indent">BUSINESS ADDRESS</td>
                <td class="border border-dark row-value" colspan="2">___data___</td> 
                <td class="border border-dark row-value" >___data___</td> 
                <td class="border border-dark row-value" >___data___</td> 
            </tr>  
            
            <tr>
                <td class="border border-dark title title-indent">TELEPHONE NO.</td>
                <td class="border border-dark row-value" colspan="2">___data___</td> 
                <td class="border border-dark row-value" >___data___</td> 
                <td class="border border-dark row-value" >___data___</td> 
            </tr>    

            {{-- FATHER --}}
            <tr>
                <td class="border-right border-dark title">24. FATHER'S SURNAME</td>
                <td class="border border-dark row-value" colspan="2">___data___</td>
                <td class="border border-dark row-value" >___data___</td>
                <td class="border border-dark row-value" >___data___</td>
            </tr>
            <tr>    
                <td class="ml-3 title title-indent" > FIRST NAME</td> 
                <td class="border border-dark row-value" >___data___</td> 
                <td class="border border-dark bg-title" style="width: 21%">
                    <div style="font-size: 6px; margin-bottom: 0px">NAME EXTENSION (JR., SR) <div class="row-value">___data___</div> </div> 
                   
                </td>
                <td class="border border-dark row-value" >___data___</td> 
                <td class="border border-dark row-value" >___data___</td> 
            </tr>
            <tr>
                <td class="border-bottom border-dark title title-indent">Middle name</td>
                <td colspan="2" class="border border-dark row-value">___data___</td> 
                <td class="border border-dark row-value" >___data___</td>
                <td class="border border-dark row-value" >___data___</td>  
            </tr>  

            {{-- MOTHER --}}
            <tr>
                <td class="border-right border-dark title">24. MOTHER'S MAIDEN NAME</td>
                <td class="border border-dark row-value" colspan="2">___data___</td>
                <td class="border border-dark row-value" >___data___</td>
                <td class="border border-dark row-value" >___data___</td>
            </tr>
            <tr>    
                <td class="ml-3 title title-indent" > SURNAME</td> 
                <td class="border border-dark row-value" colspan="2">___data___</td>
                <td class="border border-dark row-value" >___data___</td>
                <td class="border border-dark row-value" >___data___</td>
            </tr>
            <tr>    
                <td class="ml-3 title title-indent" > FIRST NAME</td> 
                <td class="border border-dark row-value" colspan="2">___data___</td>
                <td class="border border-dark row-value" >___data___</td>
                <td class="border border-dark row-value" >___data___</td>
            </tr>
            <tr>
                <td class="border-bottom border-dark title title-indent">Middle name</td>
                <td colspan="2" class="border border-dark row-value">___data___</td> 
                
                <td class="border border-dark row-value footer-continue" colspan="8">
                    (Continue on separate sheet if necessary)
                </td>   
            </tr> 
        </table>

        {{-- EDUCATION --}}
        <h5 class="section-header fst-italic">
            III.  EDUCATIONAL BACKGROUND
        </h5>

        <table cellspacing="0" cellpadding="0" class="border border-dark table-section">
            <tr>
                <td class="border border-dark title" rowspan="2" >26. <div style="text-align: center">LEVEL</div></td>
                <td class="border border-dark title text-center" rowspan="2" style="width: 25%">NAME OF SCHOOl <br/> (Write in full)</td>
                <td class="border border-dark title text-center" rowspan="2" style="width: 25%">BASIC EDUCATION/DEGREE/COURSE (Write in full)</td>
                <td class="border border-dark title text-center" colspan="2">PERIOD OF ATTENDANCE</td>
                <td class="border border-dark title text-center" rowspan="2" style="width: 10%">HIGHEST LEVEL/ <br/> UNITS EARNED <br/> (if not graduated)</td>
                <td class="border border-dark title text-center" rowspan="2" style="width: 5%">YEAR GRADUATED</td>
                <td class="border border-dark title text-center" rowspan="2" style="width: 12%">SCHOLARSHIP/ ACADEMIC HONORS RECEIVED</td>
            </tr>

            <tr>
                <td class="border border-dark title text-center" style="width: 7%">From</td>
                <td class="border border-dark title text-center" style="width: 7%">To</td>
            </tr>

            {{-- ELEMENTARY --}}
            <tr >
                <td class="border border-dark title py-3 text-indent" style="width: 22%">ELEMENTARY</td>
                @for($i = 0; $i < 7; $i++)
                    <td class="border border-dark row-value"></td>
                @endfor
            </tr>

        {{-- SECONDARY --}}
            <tr>
                <td class="border border-dark title py-3 text-indent">SECONDARY</td>
                @for($i = 0; $i < 7; $i++)
                    <td class="border border-dark row-value"></td>
                @endfor
            </tr>

            {{-- VOCATIONAL/TRADE COURSE --}}
            <tr>
                <td class="border border-dark title py-3 text-indent" >VOCATIONAL/TRADE COURSE </td>
                @for($i = 0; $i < 7; $i++)
                    <td class="border border-dark row-value"></td>
                @endfor
            </tr>

            {{-- COLLEGE --}}
            <tr>
                <td class="border border-dark title py-3 text-indent" >COLLEGE</td>
                @for($i = 0; $i < 7; $i++)
                    <td class="border border-dark row-value"></td>
                @endfor
            </tr>

            {{-- GRADUATE STUDIES  --}}
            <tr>
                <td class="border border-dark title py-3 text-indent" > GRADUATE STUDIES</td>
                @for($i = 0; $i < 7; $i++)
                    <td class="border border-dark row-value"></td>
                @endfor
            </tr>

            {{-- FOOTER --}}
            <tr>
                <td class="border border-dark row-value footer-continue py-0" colspan="8">
                    (Continue on separate sheet if necessary)
                </td>
            </tr>

            <tr>
                <td class="border border-dark title py-2">
                    <div class="fs-5 fw-bold fst-italic text-center ">Signature</div>
                </td>

                <td class="border border-dark row-value" colspan="2" >
                
                </td>

                <td class="border border-dark title" colspan="2">
                    <div class="fs-5 fw-bold fst-italic text-center ">Date</div>
                </td>

                <td class="border border-dark" colspan="4">
                
                </td>
            </tr>
            
            <tr>
                <td class="border border-dark row-value py-0 fst-italic " colspan="8" style="font-size: 7px; text-align: right">
                    CS FORM 212 (Revised 2017), Page 1 of 4
                </td>
            </tr>

        </table>

    </div>
   



    {{-- BOOTSTRAP --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>

