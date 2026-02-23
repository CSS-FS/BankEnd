<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ ucfirst($parameter) }} Data Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #1e40af;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            color: #6b7280;
            font-size: 14px;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 10px;
            width: 30%;
        }
        .info-value {
            display: table-cell;
            padding: 5px 10px;
        }
        .statistics {
            margin: 20px 0;
            padding: 15px;
            background: #ecfccb;
            border-left: 4px solid #84cc16;
        }
        .statistics h3 {
            color: #365314;
            margin-bottom: 10px;
        }
        .stat-grid {
            display: table;
            width: 100%;
        }
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
        }
        .stat-label {
            font-size: 12px;
            color: #4b5563;
            text-transform: uppercase;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
        }
        .alerts {
            margin: 20px 0;
            padding: 15px;
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .alerts h3 {
            color: #78350f;
            margin-bottom: 10px;
        }
        .alert-grid {
            display: table;
            width: 100%;
        }
        .alert-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }
        table thead {
            background: #3b82f6;
            color: white;
        }
        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        table tbody tr:hover {
            background: #f3f4f6;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ ucfirst(str_replace('_', ' ', $parameter)) }} Data Report</h1>
        <p>{{ $shed->name }} | {{ $from }} to {{ $to }}</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Shed Name:</div>
            <div class="info-value">{{ $shed->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Parameter:</div>
            <div class="info-value">{{ ucfirst(str_replace('_', ' ', $parameter)) }} @if($unit)({{ $unit }})@endif</div>
        </div>
        <div class="info-row">
            <div class="info-label">Time Range:</div>
            <div class="info-value">{{ ucfirst(str_replace('_', ' ', $time_range)) }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Date Range:</div>
            <div class="info-value">{{ $from }} to {{ $to }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Generated:</div>
            <div class="info-value">{{ now()->format('Y-m-d H:i:s') }}</div>
        </div>
    </div>

    <div class="statistics">
        <h3>Statistics Summary</h3>
        <div class="stat-grid">
            <div class="stat-item">
                <div class="stat-label">Minimum</div>
                <div class="stat-value">{{ $statistics['min'] ?? 'N/A' }} @if($unit){{ $unit }}@endif</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Average</div>
                <div class="stat-value">{{ $statistics['avg'] ?? 'N/A' }} @if($unit){{ $unit }}@endif</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Maximum</div>
                <div class="stat-value">{{ $statistics['max'] ?? 'N/A' }} @if($unit){{ $unit }}@endif</div>
            </div>
        </div>
    </div>

    @if($alert_thresholds)
    <div class="alerts">
        <h3>Alert Thresholds</h3>
        <div class="alert-grid">
            <div class="alert-item">
                <div class="stat-label">Low Alert</div>
                <div class="stat-value" style="color: #dc2626;">{{ $alert_thresholds['low'] }} @if($unit){{ $unit }}@endif</div>
            </div>
            <div class="alert-item">
                <div class="stat-label">High Alert</div>
                <div class="stat-value" style="color: #dc2626;">{{ $alert_thresholds['high'] }} @if($unit){{ $unit }}@endif</div>
            </div>
        </div>
    </div>
    @endif

    @if(count($logs) > 0)
    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Average Value @if($unit)({{ $unit }})@endif</th>
                <th>Min Value @if($unit)({{ $unit }})@endif</th>
                <th>Max Value @if($unit)({{ $unit }})@endif</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ $log['timestamp'] }}</td>
                <td>{{ number_format($log['value'], 2) }}</td>
                <td>{{ number_format($log['min'], 2) }}</td>
                <td>{{ number_format($log['max'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        <p>No data available for the selected time range.</p>
    </div>
    @endif

    <div class="footer">
        <p>Flock-Sense IoT Monitoring System | Generated on {{ now()->format('F d, Y') }}</p>
    </div>
</body>
</html>
