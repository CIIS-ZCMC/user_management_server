<?php
// Generate the current date and time
    $currentDateTime = date('F j, Y, g:i a');;
?>


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

    </style>

    
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif;">
    <div class="container">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <!-- Your content goes here -->
                <div style="display: inline-block; text-align: center;">
                    <img style="width:80px" src="https://th.bing.com/th/id/R.4ae65110f08f0d39558fd28c2cc01bd8?rik=zU9J5LxP9cw%2bbw&riu=http%3a%2f%2fdai.global-intelligent-solutions.com%2fimg%2fclients%2fzcmc.png&ehk=jWGGGeHJrilA0FTl4weHQ%2ff0L1diRoZfPim1tkB87eA%3d&risl=&pid=ImgRaw&r=0" alt="">
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
      
        <h3>Date Sent: <?php echo $currentDateTime; ?></h2>
        <h4>Your ( OTP ) One Time Pin is</h4>
        <h1>{{$otpcode}}</h1>
        <h5 style="font-weight: normal">No one can access your account without accessing this email.<br>Don't share this with anyone.</h5>
        <br>
        <h4>
           <span id="zcmc">ZCMC</span>-Portal &middot; 2023
           <br>
           
        </h4>
    </div>
</body>
</html>