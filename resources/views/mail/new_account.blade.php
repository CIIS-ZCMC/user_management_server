<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        .container {
            padding: 100px;
            text-align: center
        }
        h1 {
           font-size: 45px
        }
        #zcmc {
            color:green
        }

        #titleBar {
            text-align: center;
            display: flex;
            justify-content: center; /* Center the content inside titleBar */
            align-items: center; /* Center vertically */
        }
                #titleBar > * {
            margin-right: 5px; 
        }

        #titleBar > *:last-child {
            margin-right: 0; 
        }
        #zcmclogo {
            width: 70px;
      
        }
        #dohlogo {
            width: 85px
        }
        
        #employee_id {
            color:'red'
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif;">
    <div class="container">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <!-- Your content goes here -->
                <div style="display: inline-block; text-align: center;">
                    <img src="https://lh3.googleusercontent.com/u/5/drive-viewer/AK7aPaDXndJxYPsDExNw2fajIjCTr-qmQrgsf6Qa0fGMVmN2YsMBdn7gt3a6-m2RBG-wdHAhYJt5-QyNDax-_0yEc9mzoV4whw=w1912-h912" alt="">
                    {{-- <img id="zcmclogo"
                        src="https://lh3.googleusercontent.com/u/5/drive-viewer/AK7aPaC2xl2fqo00SR1CvYcXNKjbjeLA3FnLr_eSVSbQS9JNiN0kwRdElhoeljpriKqmrA6Xq8zLvfvvmfmBFO8Nm-v61D_q=w1912-h912"
                        alt="zcmcLogo" style="width: 50px; margin-right: 5px;">
                    <div id="word" style="display: inline-block; vertical-align: top; text-align: center;">
                        <span id="rotp" style="color: #000; font-size: 12px;">Republic of the Philippines<br>Department
                            of Health</span>
                        <br>
                        <span id="zcmc" style="color: green; font-size: 13px;">ZAMBOANGA CITY MEDICAL CENTER</span>
                        <br>
                        <span id="addr" style="color: #000; font-size: 11px;">DR. EVANGELISTA ST., STA. CATALINA,
                            ZAMBOANGA CITY</span>
                    </div>
                    <img id="dohlogo"
                        src="https://lh3.googleusercontent.com/u/5/drive-viewer/AK7aPaDvkpGzl6MQBJChSCtixNua-eAJszbe6xKP4rY8t1TPmtQOvxojVkP5PHF0bhjdbXO41vuNiYW4ngpIfraBAEm7nAvY1A=w1912-h912"
                        alt="dohLogo" style="width: 65px; margin-left: 0px;"> --}}
                </div>
            </td>
        </tr>
    </table>
        <h4>Your account for Zcmc Portal</h4>

        <span style="font-size: 14px">EMPLOYEE ID: <span style="font-weight:600">test</span></span><br/>
        <span style="font-size: 14px">PASSWORD:  <span style="font-weight:600">test</span></span>
        <h5 style="font-weight: normal">You need to register your biometric in IHOMP.<br/>No one can access your account without accessing this email.<br>Don't share this with anyone.</h5>
        <br>
        <h4>
           <span id="zcmc">ZCMC</span>-Portal &middot; 2023
           <br>
           
        </h4>
    </div>
</body>
</html>