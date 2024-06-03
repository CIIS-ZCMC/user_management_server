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
        <h1>New Leave Request Submitted</h1>
        <p>Dear {{ $data['name'] }},</p>
        <p>An employee has submitted a new leave request. Here are the details:</p>
        <ul>
            <li><strong>Employee ID:</strong> {{ $data['employeeID'] }}</li>
            <li><strong>Employee Name:</strong> {{ $data['employeeName'] }}</li>
            <li><strong>Leave Type:</strong> {{ $data['leaveType'] }}</li>
            <li><strong>Start Date:</strong> {{ $data['dateFrom'] }}</li>
            <li><strong>End Date:</strong> {{ $data['dateTo'] }}</li>
        </ul>
        <p>To review the request and take the necessary actions, please click on this <a href="{{ $Link }}">link</a>.</p>
        <p>Thank you.</p>
        <div class="system-info">
            <p>This notification was sent by the ZCMC Portal. For more information, please visit our portal or contact support.</p>
        </div>
        <p class="footer">This is an automated message, please do not reply.</p>
    </div>
</body>

</html>
