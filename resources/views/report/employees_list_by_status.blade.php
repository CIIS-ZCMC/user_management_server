<!DOCTYPE html>
<html>
<head>
    <title>Employee Biometric Enrollment Report - ZCMC</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: Arial, sans-serif; 
            font-size: 11px; 
            margin: 20px;
            color: #333;
            line-height: 1.4;
        }

        /* Header Styles */
        .header {
            width: 100%;
            text-align: center;
            margin-bottom: 30px;
        }

        .header-container {
            display: table;
            width: 100%;
            margin: 0 auto;
        }

        .header-row {
            display: table-row;
        }

        .header-cell {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        .logo-cell {
            width: 15%;
        }

        .text-cell {
            width: 70%;
            padding: 0 20px;
        }

        .logo {
            height: 70px;
            width: auto;
        }

        .header-text {
            line-height: 1.3;
        }

        .header-text .country {
            font-size: 12px;
            color: #555;
            margin-bottom: 3px;
        }

        .header-text .hospital-name {
            font-size: 16px;
            font-weight: bold;
            color: #49a227;
            margin: 5px 0;
        }

        .header-text .address {
            font-size: 11px;
            color: #555;
            margin-top: 3px;
        }

        /* Report Title */
        .report-title {
            text-align: center;
            margin: 20px 0;
            padding: 15px 0;
            border-top: 2px solid #555;
            border-bottom: 2px solid #555;
        }

        .report-title h1 {
            font-size: 18px;
            color: #49a227;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .report-title .subtitle {
            font-size: 12px;
            color: #555;
        }

        /* Report Meta Information */
        .report-meta { 
            background-color: #f8f9fa;
            padding: 12px;
            margin-bottom: 25px;
            border-radius: 5px;
            border-left: 4px solid #49a227;
        }

        .meta-grid {
            display: table;
            width: 100%;
        }

        .meta-row {
            display: table-row;
        }

        .meta-cell {
            display: table-cell;
            width: 33.33%;
            padding: 3px 10px 3px 0;
            font-size: 10px;
        }

        .meta-label {
            font-weight: bold;
            color: #555;
        }

        /* Summary Section */
        .summary-section {
            margin-bottom: 30px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }

        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #555;
            margin-bottom: 15px;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-row {
            display: table-row;
        }

        .summary-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 10px;
            background-color: white;
            border: 1px solid #dee2e6;
            margin: 2px;
        }

        .summary-label {
            font-size: 10px;
            color: #555;
            margin-bottom: 5px;
        }

        .summary-number {
            font-size: 20px;
            font-weight: bold;
            color: #49a227;
        }

        .summary-number.warning {
            color: #dc3545;
        }

        /* Section Styles */
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .section-header {
            background-color: #49a227;
            color: white;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 3px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin: 0;
        }

        .section-count {
            font-size: 11px;
            opacity: 0.9;
        }

        /* Table Styles */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px;
            font-size: 10px;
            background-color: white;
            border: 1px solid #dee2e6;
        }

        th { 
            background-color: #495057;
            color: white;
            text-align: left; 
            padding: 8px 6px; 
            font-weight: bold;
            font-size: 9px;
            border-bottom: 2px solid #343a40;
        }

        td { 
            padding: 6px; 
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #e9ecef;
        }

        /* Status Badges */
        .status-badge {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-active { 
            background-color: #d4edda;
            color: #155724;
        }

        .status-warning { 
            background-color: #fff3cd;
            color: #856404;
        }

        .status-danger { 
            background-color: #f8d7da;
            color: #721c24;
        }

        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            font-style: italic;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px dashed #dee2e6;
        }

        .no-data .icon {
            font-size: 24px;
            margin-bottom: 10px;
            color: #28a745;
        }

        /* Footer Styles */
        .footer { 
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center; 
            font-size: 9px; 
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding: 10px 0;
            background-color: white;
        }
        
        .footer-content {
            line-height: 1.3;
        }

        .company-name {
            font-weight: bold;
            color: #49a227;
            font-size: 10px;
        }

        .confidential {
            color: #dc3545;
            font-weight: bold;
            margin: 3px 0;
        }

        /* Page Break */
        .page-break {
            page-break-before: always;
        }

        /* Print Optimizations */
        @media print {
            body {
                margin: 15px;
            }
            
            .section {
                page-break-inside: avoid;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Header with Logos -->
    <div class="header">
        <div class="header-container">
            <div class="header-row">
                <div class="header-cell logo-cell">
                    <img class="logo" src="{{ base_path() . '/public/storage/logo/zcmc.jpeg' }}" alt="ZCMC Logo">
                </div>
                <div class="header-cell text-cell">
                    <div class="header-text">
                        <div class="country">Republic of the Philippines</div>
                        <div class="hospital-name">ZAMBOANGA CITY MEDICAL CENTER</div>
                        <div class="address">Dr. Evangelista Street, Sta. Catalina, Zamboanga City</div>
                    </div>
                </div>
                <div class="header-cell logo-cell">
                    <img class="logo" src="{{ base_path() . '/public/storage/logo/doh.jpeg' }}" alt="DOH Logo">
                </div>
            </div>
        </div>
    </div>

    <!-- Report Title -->
    <div class="report-title">
        <h2>Employee Biometric Enrollment Status Report</h2>
        <div class="subtitle">User Management Information System</div>
    </div>

    <!-- Report Metadata -->
    <div class="report-meta">
        <div class="meta-grid">
            <div class="meta-row">
                <div class="meta-cell">
                    <span class="meta-label">Report Generated:</span><br>
                    {{ now()->format('F j, Y \a\t g:i A') }}
                </div>
                <div class="meta-cell">
                    <span class="meta-label">Report ID:</span><br>
                    ZCMC-{{ now()->format('Ymd-His') }}
                </div>
                <div class="meta-cell">
                    <span class="meta-label">Generated By:</span><br>
                    User Management Information System
                </div>
            </div>
        </div>
    </div>

    <!-- Executive Summary -->
    <div class="summary-section">
        <div class="summary-title">Executive Summary ( Over All )</div>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-item">
                    <div class="summary-label">Total Employees</div>
                    <div class="summary-number">{{ $overAll['employees'] }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Enrolled Biometric</div>
                    <div class="summary-number">{{ $overAll['total_with_biometric']->total }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Missing Biometric</div>
                    <div class="summary-number warning">{{ $overAll['total_with_no_biometric']->total }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Enrollment Completion Rate</div>
                    <div class="summary-number">
                        {{ $overAll['total_with_biometric']->total > 0 ? number_format(($overAll['total_with_biometric']->total / $overAll['employees']) * 100, 1) : 0 }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Executive Summary Regular Only -->
    <div class="summary-section">
        <div class="summary-title">Executive Summary ( Regular Only )</div>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-item">
                    <div class="summary-label">Total Employees</div>
                    <div class="summary-number">{{ $regular['employees'] }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Enrolled Biometric</div>
                    <div class="summary-number">{{ $regular['total_with_biometric']->total }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Missing Biometric</div>
                    <div class="summary-number warning">{{ $regular['total_with_no_biometric']->total }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Enrollment Completion Rate</div>
                    <div class="summary-number">
                        {{ $regular['total_with_biometric']->total > 0 ? number_format(($regular['total_with_biometric']->total / $regular['employees']) * 100, 1) : 0 }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Executive Summary Job Order Only -->
    <div class="summary-section">
        <div class="summary-title">Executive Summary ( Job Order Only )</div>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-item">
                    <div class="summary-label">Total Employees</div>
                    <div class="summary-number">{{ $jobOrder['employees'] }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Enrolled Biometric</div>
                    <div class="summary-number">{{ $jobOrder['total_with_biometric']->total }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Missing Biometric</div>
                    <div class="summary-number warning">{{ $jobOrder['total_with_no_biometric']->total }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Enrollment Completion Rate</div>
                    <div class="summary-number">
                        {{ $jobOrder['total_with_biometric']->total > 0 ? number_format(($jobOrder['total_with_biometric']->total / $jobOrder['employees']) * 100, 1) : 0 }}%
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Regular Employees Without Biometric Data -->
    <div class="section page-break">
        <div class="section-header">
            <div class="section-title">Regular Employees Requiring Biometric Enrollment</div>
            <div class="section-count">{{ $regular['total_with_no_biometric']->total }} employees need immediate attention</div>
        </div>
        
        @if(isset($regular['employeesNoBiometric']) && count($regular['employeesNoBiometric']) > 0)
        <table>
            <thead>
                <tr>
                    <th width="10%">Employee ID</th>
                    <th width="25%">Full Name</th>
                    <th width="25%">Email Address</th>
                    <th width="20%">Area Assigned</th>
                    <th width="12%">Date Hired</th>
                    <th width="8%">Login Activity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($regular['employeesNoBiometric'] as $employee)
                <tr>
                    <td><strong>{{ $employee['employee_id'] }}</strong></td>
                    <td>{{ $employee['name'] }}</td>
                    <td>{{ $employee['email'] }}</td>
                    <td>{{ $employee['area'] ?? 'Not Assigned' }}</td>
                    <td>{{ \Carbon\Carbon::parse($employee['date_hired'])->format('M j, Y') }}</td>
                    <td><span class="status-badge status-warning">{{ $employee['has_login_history'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">
            <div class="icon">✅</div>
            <div><strong>Excellent!</strong></div>
            <div>All employees have completed biometric enrollment.</div>
        </div>
        @endif
    </div>
    
    <!-- Job Order Employees Without Biometric Data -->
    <div class="section page-break">
        <div class="section-header">
            <div class="section-title">Job Order Employees Requiring Biometric Enrollment</div>
            <div class="section-count">{{ $jobOrder['total_with_no_biometric']->total }} employees need immediate attention</div>
        </div>
        
        @if(isset($jobOrder['employeesNoBiometric']) && count($jobOrder['employeesNoBiometric']) > 0)
        <table>
            <thead>
                <tr>
                    <th width="10%">Employee ID</th>
                    <th width="25%">Full Name</th>
                    <th width="25%">Email Address</th>
                    <th width="20%">Area Assigned</th>
                    <th width="12%">Date Hired</th>
                    <th width="8%">Login Activity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($jobOrder['employeesNoBiometric'] as $employee)
                <tr>
                    <td><strong>{{ $employee['employee_id'] }}</strong></td>
                    <td>{{ $employee['name'] }}</td>
                    <td>{{ $employee['email'] }}</td>
                    <td>{{ $employee['area'] ?? 'Not Assigned' }}</td>
                    <td>{{ \Carbon\Carbon::parse($employee['date_hired'])->format('M j, Y') }}</td>
                    <td><span class="status-badge status-warning">{{ $employee['has_login_history']}}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">
            <div class="icon">✅</div>
            <div><strong>Excellent!</strong></div>
            <div>All employees have completed biometric enrollment.</div>
        </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <div class="company-name">Zamboanga City Medical Center</div>
            <div>Human Resources Information System</div>
            <div class="confidential">CONFIDENTIAL EMPLOYEE INFORMATION</div>
            <div>© {{ date('Y') }} ZCMC - Generated: {{ now()->format('F j, Y \a\t g:i:s A') }}</div>
        </div>
    </div>
</body>
</html>