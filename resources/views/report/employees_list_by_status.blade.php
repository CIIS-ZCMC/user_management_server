<!DOCTYPE html>
<html>
<head>
    <title>Employee Status Report - ZCMC</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11px; 
            margin: 15px;
            color: #333;
        }

        header {
            width: 90%;
            text-align: center;
            display: table;
            margin: auto;
        }

        .header-container {
            display: table-row;
        }

        .header-item {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        #zcmclogo,
        #dohlogo {
            height: 65px;
        }

        .header-text {
            text-align: center;
        }

        .header-text h5 {
            margin: 0;
        }

        .logo-container {
            width: 20%;
        }

        .text-container {
            width: 60%;
        }

        /* Horizontal Divider */
        .divider {
            width: 80%;
            border-top: 1px solid rgb(212, 212, 212);
            margin: 25px 10%;
        }
        
        .report-meta { 
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 3px solid #2c5aa0;
        }

        .meta-row {
            margin-bottom: 5px;
        }
        
        .summary-section {
            margin-bottom: 25px;
            background-color: #f9f9f9;
            padding: 15px;
        }

        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 10px;
        }
        
        .summary-item {
            display: flex;
            gap: 5px;
        }

        .summary-section p {
            margin: 8px 0;
            font-size: 12px;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px;
            font-size: 10px;
        }
        th { 
            background-color: #2c5aa0;
            color: white;
            text-align: left; 
            padding: 6px 4px; 
            font-weight: bold;
            font-size: 9px;
        }
        td { 
            padding: 4px; 
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .status-active { color: #28a745; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .status-danger { color: #dc3545; font-weight: bold; }
        
        .footer { 
            margin-top: 30px; 
            text-align: center; 
            font-size: 9px; 
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .company-name {
            font-weight: bold;
            color: #2c5aa0;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="header-item logo-container">
                <img id="zcmclogo" src="{{ base_path() . '/public/storage/logo/zcmc.jpeg' }}" alt="ZCMC Logo">
            </div>
            <div class="header-item text-container header-text">
                <span>Republic of the Philippines</span>
                <h5>ZAMBOANGA CITY MEDICAL CENTER</h5>
                <span>Dr. Evangelista Street, Sta. Catalina, Zamboanga City</span>
            </div>
            <div class="header-item logo-container">
                <img id="dohlogo" src="{{ base_path() . '/public/storage/logo/doh.jpeg' }}" alt="DOH Logo">
            </div>
        </div>
    </header>

    <!-- Horizontal Divider -->
    <div class="divider"></div>

    <div class="report-meta">
        <div class="meta-row"><strong>Report Generated:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</div>
        <div class="meta-row"><strong>Downloaded:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</div>
        <div class="meta-row"><strong>Report ID:</strong> ZCMC-{{ now()->format('Ymd-His') }}</div>
    </div>

    <div class="summary-section">
        <div class="summary-title">Executive Summary</div>
        <div class="summary-item">
            <div>Total Active Employees</div>
            <div class="summary-number">{{ $employees }}</div>
        </div>
        <div class="summary-item">
            <div>Missing Biometric Data</div>
            <div class="summary-number">{{ count($employees_no_biometric ?? []) }}</div>
        </div>
        <div class="summary-item">
            <div>No Login History</div>
            <div class="summary-number">{{ count($employees_no_login ?? []) }}</div>
        </div>
    </div>
    
    <div class="section page-break">
        <div class="section-title">Employees Without Biometric Data ({{ count($employees_no_biometric ?? []) }} records)</div>
        @if(isset($employees_no_biometric) && count($employees_no_biometric) > 0)
        <table>
            <thead>
                <tr>
                    <th width="8%">Employee ID</th>
                    <th width="30%">Name</th>
                    <th width="30%">Email</th>
                    <th width="17%">Area Assigned</th>
                    <th width="12%">Date Hired</th>
                    <th width="10%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees_no_biometric as $employee)
                <tr>
                    <td>{{ $employee['employee_id'] }}</td>
                    <td>{{ $employee['name'] }}</td>
                    <td>{{ $employee['email'] }}</td>
                    <td>{{ $employee['area'] ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($employee['date_hired'])->format('M j, Y') }}</td>
                    <td><span class="status-warning">Enrollment Required</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p><em>All employees have completed biometric enrollment.</em></p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Employees Without Login History ({{ count($employees_no_login ?? []) }} records)</div>
        @if(isset($employees_no_login) && count($employees_no_login) > 0)
        <table>
            <thead>
                <tr>
                    <th width="8%">Employee ID</th>
                    <th width="30%">Name</th>
                    <th width="30%">Email</th>
                    <th width="17%">Area Assigned</th>
                    <th width="12%">Date Hired</th>
                    <th width="15%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees_no_login as $employee)
                <tr>
                    <td>{{ $employee['employee_id'] }}</td>
                    <td>{{ $employee['name'] }}</td>
                    <td>{{ $employee['email'] }}</td>
                    <td>{{ $employee['area'] ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($employee['date_hired'])->format('M j, Y') }}</td>
                    <td><span class="status-danger">Never Logged In</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p><em>All employees have system login activity.</em></p>
        @endif
    </div>

    <div class="footer">
        <div class="company-name">Zamboanga City Medical Center</div>
        <div>Human Resources Information System</div>
        <div>© {{ date('Y') }} ZCMC. Confidential Employee Information.</div>
        <div>Generated: {{ now()->format('F j, Y \a\t g:i:s A') }}</div>
    </div>
</body>
</html>