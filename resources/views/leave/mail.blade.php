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
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif;">
<div class="container">
        <h1>Leave Status Update</h1>
        <p>Dear {{ $name }},</p>
        <p>We would like to inform you about the status of your leave application:</p>
        <ul>
            <li><strong>Leave Type:</strong> {{ $leaveStatus->type }}</li>
            <li><strong>Start Date:</strong> {{ $leaveStatus->start_date }}</li>
            <li><strong>End Date:</strong> {{ $leaveStatus->end_date }}</li>
            <li><strong>Status:</strong> {{ $leaveStatus->status }}</li>
            <li><strong>Remarks:</strong> {{ $leaveStatus->remarks }}</li>
        </ul>
        <p>If you have any questions, please contact {{$contact}}.</p>
        <p>Best regards,</p>
        <p>{{$regards}}</p>
        <p class="footer">This is an automated message, please do not reply.</p>
    </div>
</body>
</html>
