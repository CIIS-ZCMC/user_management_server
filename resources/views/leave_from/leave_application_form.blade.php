<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application for Leave</title>
    {{-- <link rel="stylesheet" href="style.css"> --}}
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    header {
        text-align: center;
        margin-bottom: 20px;
    }

    section {
        border: 1px solid black;
        padding: 20px;
    }

    .row {
        margin-bottom: 10px;
    }

    label {
        display: block;
        margin-bottom: 5px;
    }

    input[type="text"],
    input[type="radio"] {
        width: 100%;
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
    }

    /* Styling for specific sections */
    .application-form h2 {
        text-align: center;
        margin-bottom: 20px;
    }

    .personal-information {
        margin-bottom: 20px;
    }

    .details-of-application {
        margin-bottom: 20px;
    }

    .signature {
        text-align: center;
    }

    .signature img {
        width: 150px;
        height: 50px;
        margin-bottom: 10px;
    }

    footer {
        text-align: center;
        margin-top: 20px;
    }
    </style>
    </head>
    <body>
    <header>
        <h1>Republic of the Philippines</h1>
        <h2>Department of Health</h2>
        <h3>ZAMBOANGA CITY MEDICAL CENTER</h3>
    </header>
    <section class="application-form">
        <h2>APPLICATION FOR LEAVE</h2>
        <div class="personal-information">
        <div class="row">
            <label for="office-agency">Office/Agency:</label>
            <input type="text" id="office-agency" value="Zamboanga City Medical Center">
        </div>
        <div class="row">
            <label for="name">Name:</label>
            <input type="text" id="name" value="Agcaoill, Adrian Asa">
        </div>
        <div class="row">
            <label for="date-of-filing">Date of Filing:</label>
            <input type="text" id="date-of-filing" value="May 29, 2023">
        </div>
        <div class="row">
            <label for="position">Position:</label>
            <input type="text" id="position" value="Nursing Attendant l">
        </div>
        <div class="row">
            <label for="salary">Salary:</label>
            <input type="text" id="salary" value="">
        </div>
        </div>
        <div class="details-of-application">
        <h3>Details of Application</h3>
        <div class="type-of-leave">
            <label for="type-of-leave">Type of Leave to be Availed Of:</label>
            <br>
            <input type="radio" id="sick-leave" name="type-of-leave" value="Sick Leave">
            <label for="sick-leave">Sick Leave (Sec. 43, Title XV, Omnibus Rules Implementing EO No. 292)</label>
            <br>
            <input type="radio" id="vacation-leave" name="type-of-leave" value="Vacation Leave">
            <label for="vacation-leave">Vacation Leave (RA No. 8187/CSC MC No. 71, s. 1968 as amended)</label>
            <br>
            <input type="radio" id="others" name="type-of-leave" value="Others">
            <label for="others">Others:</label>
            <input type="text" id="others-type" placeholder="Specify">
        </div>
        <div class="number-of-days">
            <label for="number-of-days">Number of Working Days Applied For:</label>
            <input type="text" id="number-of-days" value="1.0 day(s)">
        </div>
        <div class="commutation">
            <label for="commutation">Commutation:</label>
            <br>
            <input type="radio" id="commutation-requested" name="commutation" value="Requested">
            <label for="commutation-requested">Requested</label>
            <br>
            <input type="radio" id="commutation-not-requested" name="commutation" value="Not Requested" checked>
            <label for="commutation-not-requested">Not Requested</label>
        </div>
        <div class="inclusive-dates">
            <label for="inclusive-dates">Inclusive Dates:</label>
            <input type="text" id="inclusive-dates" value="May 25, 2023">
        </div>
        </div>
        <div class="signature">
        <p>Signature of Applicant:</p>
        <img src="signature.png" alt="Applicant Signature">
        <p>ADRIAN A. AGCAOILI</p>
        </div>
    </section>
    <footer>
        <p>ZAMBOANGA CITY MEDICAL CENTER</p>
        <p>EVANGELISTA ST. STA CATALINA, ZAM</p>
        <p>BOANGA CITY TELEFAX NO. (062) 991-2064</p>
    </footer>
    </body>
</html>
