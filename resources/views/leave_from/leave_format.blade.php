<!-- resources/views/leave_application.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Application</title>

    <style>
        @media print {
            /* Define print styles here */
            body {
                font-size: 12pt;
                width: 8.5in; /* Adjust as needed for long bond paper width */
                height: 13in; /* Adjust as needed for long bond paper height */
            }

            /* Hide unnecessary elements */
            form {
                display: block;
            }

            /* Add more styles as needed */
        }


        body {
            margin-left: 20px; /* Add left margin */
            margin-right: 20px; /* Add right margin */
            margin-top: 0; /* Reset default margin-top */
            margin-bottom: 0; /* Reset default margin-bottom */
            border: none;
        }

        /* Additional styles for the header */
        header {
            font-size: 12pt;
            font-weight: bold;
            text-align: left;
            margin-left: 20px;
            border: none; /* Remove default border */

        }

        /* Additional styles for the revision info */
        .revision-info {
            font-size: 12pt;
            text-align: left;
            margin-left: 20px;
        }

        /* Additional styles for the department info */
        .department-info {
            font-size: 11pt;
            text-align: center;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            margin-top: 20px; /* Adjust as needed */
            margin-bottom: 10px; /* Adjust as needed */
        }

        /* Placeholder style for the logo */
        .logo {
            max-width: 15%; /* Adjust as needed */
            height: auto;
            margin-right: 20px;
            margin-left: 20px; /* Adjust as needed for spacing between logos */
        }

        /* Different font size for the larger text */
        .larger-text {
            font-size: 14pt; /* Adjust as needed */
            margin-bottom: 0; /* Remove extra space below the text */
            font-weight: medium;
        }


        /* Additional styles for the application heading */
        .application-heading {
            font-size: 20pt;
            font-weight: bold;
            text-align: center;
            margin-top: 20px;

        }

        /* Additional styles for the table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px; /* Adjust as needed */
        }

        th, td {
            text-align: left;
            border: none; /* Remove default borders */
        }
    </style>
</head>
<body>

<!-- Header with the Civil Service Form No. 6 -->
<header>
    Civil Service Form No. 6
</header>

<!-- Revision info -->
<div class="revision-info">
    Revised 2020
</div>

<!-- Department information -->
<div class="department-info">
    <div class="logo">
        <img src="path/to/left_logo.png" alt="Left Logo" class="logo">
    </div>
    <div class="text">
        Republic of the Philippines<br>
        Department of Health<br>
        <span class="larger-text">ZAMBOANGA CITY MEDICAL CENTER</span><br>
        DR. EVANGELISTA ST., STA. CATALINA, ZAMBOANGA CITY
    </div>
    <div class="logo">
        <img src="path/to/right_logo.png" alt="Right Logo" class="logo">
    </div>
</div>

<!-- Application heading -->
<div class="application-heading">
    APPLICATION FOR LEAVE
</div>

<!-- Table with borders and different column widths -->
<table class="styled-table">
    <tr>
        <th class="first-column" colspan="2">1. OFFICE / AGENCY<br>Zamboanga City Medical Center</th>
        <th class="second-column">
            2. NAME (First) (Middle) (Last) <br>
            <span style="text-align: justify;">Ricah&nbsp;&nbsp;&nbsp;Mae&nbsp;&nbsp;&nbsp;Maque</span>
        </th>
    </tr>
</table>

<!-- Leave application form -->
<form>
    <!-- Your form fields go here -->
</form>

<!-- ... (remaining HTML code) ... -->

<style>
    /* CSS styles for the table */
    .styled-table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 20px; /* Adjust as needed */
    }

    .styled-table th, .styled-table td {
        border: 1px solid black;
        padding: 8px;
        text-align: left;
        vertical-align: top; /* Align content to the top */
    }

    /* Remove bottom border for the header row */
    .styled-table th.first-column, .styled-table th.second-column {
        border-bottom: none;
    }

    /* Adjust widths for better alignment */
    .styled-table th.first-column, .styled-table td.first-column {
        width: 30%;
    }

    .styled-table th.second-column, .styled-table td.second-column {
        width: 70%;
    }

    .styled-table span {
        text-align: justify;
    }
</style>
</body>
</html>
