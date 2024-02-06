<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        .container {
          
            text-align: left
        }
        h1 {
           font-size: 45px
        }
        #zcmc {
            color:green
        }

        /* #titleBar {
            text-align: center;
            display: flex;
            justify-content: center; 
            align-items: center;
        } */
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
        <img style="width:80px" src="https://th.bing.com/th/id/R.4ae65110f08f0d39558fd28c2cc01bd8?rik=zU9J5LxP9cw%2bbw&riu=http%3a%2f%2fdai.global-intelligent-solutions.com%2fimg%2fclients%2fzcmc.png&ehk=jWGGGeHJrilA0FTl4weHQ%2ff0L1diRoZfPim1tkB87eA%3d&risl=&pid=ImgRaw&r=0" alt="">
    {{-- <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 20px 0;">
            
                <div style="display: inline-block; text-align: left;">
                    <img style="width:80px" src="https://th.bing.com/th/id/R.4ae65110f08f0d39558fd28c2cc01bd8?rik=zU9J5LxP9cw%2bbw&riu=http%3a%2f%2fdai.global-intelligent-solutions.com%2fimg%2fclients%2fzcmc.png&ehk=jWGGGeHJrilA0FTl4weHQ%2ff0L1diRoZfPim1tkB87eA%3d&risl=&pid=ImgRaw&r=0" alt="">
              
                </div>
            </td>
        </tr>
    </table> --}}
        <h4>Welcome to the ZCMC Family!
            <br>
            <span style="font-size:14px;font-weight:normal">
                We're delighted to have you on board. Access your personalized Employee Portal for easy updates, news, documents, and more. If you have any questions, our HR team is here to assist.
           <br>
           Below are your login credentials:
         </span>
        </h4>

        
        <h3>
            <style>
                /* Style for the table */
table {
  border-collapse: collapse;
  width: 100%;
  margin-top: 20px; /* Add some top margin for spacing */
}

/* Style for table cells */
td {
  border: 1px solid rgb(19, 12, 12); /* Border color: #ddd (light gray) */
  padding: 8px;
  text-align: left;
}

/* Style for the first column (labels) */
td:first-child {
  font-size: 15px;
  font-weight: normal;
  color: gray; /* Text color: #555 (medium gray) */
}

/* Style for the second column (values) */
td:nth-child(2) {
  font-size: 16px;
  font-weight: bold;
  color: gray; /* Text color: #333 (dark gray) */
}

            </style>

            <table style="  border-collapse: collapse;
            width: 100%;
            margin-top: 20px; ">
                <tr >
                    <td style="width: 200px !important;
                    border: 1px solid rgb(150, 147, 147);
  padding: 8px;
  text-align: left;
                    "> <span style="font-size:14px;font-weight:normal">Employee-ID / Username :</span></td>
                    <td style="  border: 1px solid rgb(150, 147, 147);
                    padding: 8px;
                    text-align: left;">
                        {{$employeeID}}
                    </td>
                </tr>
                <tr>
                    <td  style="  border: 1px solid rgb(150, 147, 147);
                    padding: 8px;
                    text-align: left;"> <span style="font-size:15px;font-weight:normal">Default Password :</span></td>
                    <td  style="  border: 1px solid rgb(150, 147, 147);
                    padding: 8px;
                    text-align: left;">
                        {{$Password}}
                    </td>
                </tr>
              
                

            </table>
            <h4 style="font-weight: normal">Upon first login, you will be prompted to change your password.</h4>

           
            <table style="  border-collapse: collapse;
            width: 100%;
            margin-top: 20px; ">
               
                <tr>
                    <td  style="  border: 1px solid rgb(150, 147, 147);
                    padding: 8px;
                    text-align: left;"> <span style="font-size:15px;font-weight:normal">Website Link :</span></td>
                    <td  style="  border: 1px solid rgb(150, 147, 147);
                    padding: 8px;
                    text-align: left;">
                        <a href="{{$Link}}">Link to ZCMC-Portal 2023</a>
                    </td>
                </tr>
                <tr>
                    <td  style="  border: 1px solid rgb(150, 147, 147);
                    padding: 8px;
                    text-align: left;" colspan="2">
                        <h4 style="font-weight: normal;color:rgb(216, 68, 68)">The link is accessible only within the ZCMC premises when connected to the local network.</h4>
                    </td>
                </tr>
            </table>

        
          
        </h3>
     
     
        <h4>
           <span id="zcmc">ZCMC</span>-Portal &middot; 2023
           <br>
           
        </h4>
    </div>
</body>
</html>