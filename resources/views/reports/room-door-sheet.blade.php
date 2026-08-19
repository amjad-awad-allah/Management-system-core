<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Raumbelegungsplan - {{ $data['room_name'] }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #0f172a;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #0284c7;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            color: #0369a1;
            margin: 0 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .meta {
            font-size: 14px;
            color: #475569;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #94a3b8;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #e0f2fe;
            color: #0369a1;
            font-size: 13px;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .time-col {
            font-weight: bold;
            color: #0f172a;
            width: 20%;
        }
        .privacy-notice {
            margin-top: 25px;
            padding: 10px;
            background-color: #f1f5f9;
            border-left: 4px solid #0284c7;
            font-size: 10px;
            color: #475569;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Raumbelegungsplan</h1>
        <div class="meta">
            Raum: {{ $data['room_name'] }} | Datum: {{ $data['date'] }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Uhrzeit</th>
                <th>Fach</th>
                <th>Lehrkraft</th>
                <th>Teilnehmerzahl</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['lessons'] as $lesson)
                <tr>
                    <td class="time-col">{{ $lesson['start_time'] }} - {{ $lesson['end_time'] }} Uhr</td>
                    <td>{{ $lesson['subject'] }}</td>
                    <td>{{ $lesson['teacher_name'] }}</td>
                    <td>{{ $lesson['student_count'] }} Schüler</td>
                    <td>{{ $lesson['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">Keine Unterrichtseinheiten für diesen Raum an diesem Tag geplant.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="privacy-notice">
        <strong>Datenschutzhinweis (DSGVO):</strong> Aus Datenschutzgründen werden auf Raumbelegungsplänen ausschließlich Schüleranzahlen ausgewiesen. Namenslisten sind nicht öffentlich einzusehen.
    </div>

    <div class="footer">
        Generated: {{ $data['generated_at'] }} | Nachhilfe Management System
    </div>
</body>
</html>
