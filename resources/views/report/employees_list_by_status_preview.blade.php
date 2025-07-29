<!DOCTYPE html>
<html>
<head>
    <title>Active Employees Report - Zamboanga City Medical Center</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            font-size: 12px; 
            margin: 20px;
            background-color: #f8f9fa;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 3px solid #2c5aa0;
            padding-bottom: 20px;
        }
        .header h1 { 
            color: #2c5aa0; 
            margin: 0; 
            font-size: 28px;
            font-weight: 600;
        }
        .header h2 { 
            color: #666; 
            margin: 5px 0 0 0; 
            font-size: 18px;
            font-weight: normal;
        }
        .header .subtitle {
            color: #888;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .report-meta { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 25px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2c5aa0;
        }
        .report-meta div {
            font-weight: 500;
        }
        
        .summary-section {
            margin-bottom: 30px;
        }
        .summary-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c5aa0;
            margin-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 8px;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .summary-card.alert {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .summary-card .number {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
        }
        
        .section {
            margin-bottom: 40px;
        }
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #2c5aa0;
            margin-bottom: 15px;
            padding: 10px 0;
            border-bottom: 2px solid #e9ecef;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        th { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: left; 
            padding: 12px 10px; 
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td { 
            padding: 10px; 
            border-bottom: 1px solid #e9ecef;
            font-size: 11px;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        tr:nth-child(even) {
            background-color: #fbfbfb;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .footer { 
            margin-top: 40px; 
            text-align: center; 
            font-size: 11px; 
            color: #666;
            border-top: 2px solid #e9ecef;
            padding-top: 20px;
        }
        .footer .company-name {
            font-weight: 600;
            color: #2c5aa0;
            font-size: 12px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        
        @media print {
            body { background-color: white; }
            .container { box-shadow: none; }
            .summary-card { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Employee Status & Activity Report</h1>
            <h2>Zamboanga City Medical Center</h2>
            <div class="subtitle">Comprehensive Analysis of Employee Records, Biometric Data & System Access</div>
        </div>

        <div class="report-meta">
            <div>Report Generated: {{ now()->format('F j, Y \a\t g:i A') }}</div>
            <div>Downloaded: {{ now()->format('F j, Y \a\t g:i A') }}</div>
            <div>Report ID: ZCMC-{{ now()->format('Ymd-His') }}</div>
        </div>

        <div class="summary-section">
            <div class="summary-title">Executive Summary</div>
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total Active Employees</h3>
                    <div class="number">{{ count($employees) }}</div>
                </div>
                <div class="summary-card warning">
                    <h3>Missing Biometric Data</h3>
                    <div class="number">{{ count($employees_no_biometric ?? []) }}</div>
                </div>
                <div class="summary-card alert">
                    <h3>No Login History</h3>
                    <div class="number">{{ count($employees_no_login ?? []) }}</div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">📊 All Active Employees ({{ count($employees) }} records)</div>
            @if(count($employees) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Area Assigned</th>
                        <th>Date Hired</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                    <tr>
                        <td><strong>{{ $employee['employee_id'] }}</strong></td>
                        <td>{{ $employee['name'] }}</td>
                        <td>{{ $employee['email'] }}</td>
                        <td>{{ $employee['area'] ?? 'Not Assigned' }}</td>
                        <td>{{ \Carbon\Carbon::parse($employee['date_hired'])->format('M j, Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($employee['updated_at'])->format('M j, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="no-data">No active employees found in the system.</div>
            @endif
        </div>

        <div class="section">
            <div class="section-title">⚠️ Employees Without Biometric Data ({{ count($employees_no_biometric ?? []) }} records)</div>
            @if(isset($employees_no_biometric) && count($employees_no_biometric) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Area Assigned</th>
                        <th>Date Hired</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees_no_biometric as $employee)
                    <tr>
                        <td><strong>{{ $employee['employee_id'] }}</strong></td>
                        <td>{{ $employee['name'] }}</td>
                        <td>{{ $employee['email'] }}</td>
                        <td>{{ $employee['area'] ?? 'Not Assigned' }}</td>
                        <td>{{ \Carbon\Carbon::parse($employee['date_hired'])->format('M j, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="no-data">All employees have completed biometric enrollment. ✅</div>
            @endif
        </div>

        <div class="section">
            <div class="section-title">🔐 Employees Without Login History ({{ count($employees_no_login ?? []) }} records)</div>
            @if(isset($employees_no_login) && count($employees_no_login) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Area Assigned</th>
                        <th>Date Hired</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees_no_login as $employee)
                    <tr>    
                        <td><strong>{{ $employee['employee_id'] }}</strong></td>
                        <td>{{ $employee['name'] }}</td>
                        <td>{{ $employee['email'] }}</td>
                        <td>{{ $employee['area'] ?? 'Not Assigned' }}</td>
                        <td>{{ \Carbon\Carbon::parse($employee['date_hired'])->format('M j, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="no-data">All employees have system login activity. ✅</div>
            @endif
        </div>

        <div class="footer">
            <div class="company-name">Zamboanga City Medical Center</div>
            <div>Human Resources Information System</div>
            <div>© {{ date('Y') }} ZCMC. This report contains confidential employee information.</div>
            <div style="margin-top: 8px; font-size: 10px;">
                Generated by HRIS v2.1 | Report downloaded on {{ now()->format('F j, Y \a\t g:i:s A T') }}
            </div>
        </div>
    </div>
</body>
</html>