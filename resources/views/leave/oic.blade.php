<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 80%;
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #4CAF50;
        }

        p {
            font-size: 16px;
            line-height: 1.6;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        ul li {
            margin: 5px 0;
            border-radius: 4px;
        }

        ul li strong {
            color: #333;
        }

        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }

        .system-info {
            margin-top: 20px;
            font-size: 14px;
            color: #555;
        }

        a {
            color: #4CAF50;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>


    <div class="container">
        <div style="display: flex; align-items: center;">
            <!-- ZCMC Logo on the left -->
            <img style="width:50px; margin-right: 10px;"
                src="https://th.bing.com/th/id/R.4ae65110f08f0d39558fd28c2cc01bd8?rik=zU9J5LxP9cw%2bbw&riu=http%3a%2f%2fdai.global-intelligent-solutions.com%2fimg%2fclients%2fzcmc.png&ehk=jWGGGeHJrilA0FTl4weHQ%2ff0L1diRoZfPim1tkB87eA%3d&risl=&pid=ImgRaw&r=0"
                alt="ZCMC Logo" />

            <!-- Center Text -->
            <div style="text-align: left;">
                <p style="font-size: 12px; margin: 0; font-weight: lighter">Republic of the Philippines</p>
                <p style="font-size: 12px; margin: 0; font-weight: lighter">Department of Health</p>
                <p style="font-size: 13px; margin: 0; ">ZAMBOANGA CITY MEDICAL CENTER</p>
                <p style="font-size: 12px; margin: 0; font-weight: lighter; text-transform: uppercase;">Dr. Evangelista
                    Street, Sta. Catalina, Zamboanga City</p>
            </div>
        </div>
        <p>Dear <b>{{ $data['name'] }}</b>,</p>
        <p>You have been assigned as the Officer In Charge (OIC) for an employee who has submitted a new leave request.
            Here are the details:</p>
        <ul>
            <li><strong>Employee Name:</strong> {{ $data['employeeName'] }}</li>
            <li><strong>Start Date:</strong> {{ date(' F d, Y', strtotime($data['dateFrom'])) }}</li>
            <li><strong>End Date:</strong> {{ date(' F d, Y', strtotime($data['dateTo'])) }}</li>
        </ul>
        <p>To review the request and take the necessary actions, please click on this <a
                href="{{ $data['Link'] }}">link</a>.</p>
        <p>Thank you.</p>
        <div class="system-info">
            <p>This notification was sent by the ZCMC Portal. For more information, please visit our portal or contact
                support.</p>
        </div>
        <p class="footer">This is an automated message, please do not reply.</p>
    </div>
</body>

</html>
