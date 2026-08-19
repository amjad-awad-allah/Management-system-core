<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Gehaltsabrechnung - {{ $data['teacher_name'] }} ({{ $data['year_month'] }})</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #0f172a;
            margin: 0;
            padding: 15px;
        }
        .header {
            border-bottom: 2px solid #059669;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            color: #047857;
            margin: 0 0 5px 0;
        }
        .summary-box {
            background-color: #f0fdf4;
            border: 1px solid #a7f3d0;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .summary-grid {
            width: 100%;
        }
        .summary-grid td {
            padding: 4px 8px;
            border: none;
        }
        .amount-highlight {
            font-size: 14px;
            font-weight: bold;
            color: #047857;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.items-table th, table.items-table td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
        }
        table.items-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
        }
        table.items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .audit-box {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 9px;
            color: #64748b;
            word-break: break-all;
        }
        .footer {
            margin-top: 15px;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Honorar-Abrechnung (Freigegeben)</h1>
        <div style="font-size: 11px; color: #475569;">
            Abrechnungszeitraum: <strong>{{ $data['year_month'] }}</strong>
        </div>
    </div>

    <div class="summary-box">
        <table class="summary-grid">
            <tr>
                <td><strong>Lehrkraft:</strong> {{ $data['teacher_name'] }}</td>
                <td><strong>Status:</strong> Freigegeben (Approved)</td>
            </tr>
            <tr>
                <td><strong>Unterrichtseinheiten:</strong> {{ $data['total_completed_lessons'] }}</td>
                <td><strong>Gesamtstunden:</strong> {{ $data['total_hours'] }} Std.</td>
            </tr>
            <tr>
                <td><strong>Freigegeben am:</strong> {{ $data['approved_at'] }}</td>
                <td><strong>Freigegeben von:</strong> {{ $data['approver_name'] }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 8px;">
                    <strong>Auszahlungsbetrag:</strong> <span class="amount-highlight">{{ $data['total_amount'] }} €</span>
                </td>
            </tr>
        </table>
    </div>

    <h3>Einzelaufstellung der Unterrichtseinheiten</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Datum</th>
                <th>Fach</th>
                <th>Stunden</th>
                <th>Stundensatz (€)</th>
                <th>Gesamt (€)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['items'] as $item)
                <tr>
                    <td>{{ $item['date'] }}</td>
                    <td>{{ $item['subject'] }}</td>
                    <td>{{ $item['hours'] }}</td>
                    <td>{{ $item['hourly_rate'] }} €</td>
                    <td>{{ $item['amount'] }} €</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f1f5f9;">
                <td colspan="2" style="text-align: right;">Gesamtsumme:</td>
                <td>{{ $data['total_hours'] }}</td>
                <td>-</td>
                <td>{{ $data['total_amount'] }} €</td>
            </tr>
        </tfoot>
    </table>

    <div class="audit-box">
        <strong>Kryptografischer Revisionsstempel (SHA-256 Hash):</strong><br>
        <code>{{ $data['snapshot_hash'] }}</code><br>
        <span style="font-size: 8px; color: #94a3b8;">Hash Version: {{ $data['snapshot_hash_version'] }} | Unveränderbarer historischer Schnappschuss</span>
    </div>

    <div class="footer">
        Erstellt am: {{ $data['generated_at'] }} | Nachhilfe Management System
    </div>
</body>
</html>
