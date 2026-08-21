<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Stundenplan - {{ $data['teacher_name'] }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
            padding: 15px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .header h1 {
            font-size: 18px;
            color: #1e3a8a;
            margin: 0 0 5px 0;
        }
        .logo-img {
            max-height: 48px;
            max-width: 160px;
        }
        .meta {
            font-size: 10px;
            color: #64748b;
        }
        table.timetable {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.timetable th, table.timetable td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
        }
        table.timetable th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
        }
        table.timetable tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-completed { background-color: #dcfce7; color: #166534; }
        .status-scheduled { background-color: #dbeafe; color: #1e40af; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        .footer {
            margin-top: 20px;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
            text-align: right;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <h1>Wöchentlicher Stundenplan (Lehrkraft)</h1>
                <div class="meta">
                    <strong>Lehrkraft:</strong> {{ $data['teacher_name'] }} |
                    <strong>Zeitraum:</strong> {{ $data['start_date'] }} bis {{ $data['end_date'] }} |
                    <strong>Gesamtzahl Einheiten:</strong> {{ $data['total_lessons'] }}
                </div>
            </td>
            <td style="text-align: right;">
                @if(!empty($center['logo_base64']))
                    <img src="{{ $center['logo_base64'] }}" alt="Logo" class="logo-img" /><br>
                @endif
                <strong style="font-size: 12px; color: #1e3a8a;">{{ $center['name'] ?? 'Muster Nachhilfeinstitut' }}</strong>
            </td>
        </tr>
    </table>

    <table class="timetable">
        <thead>
            <tr>
                <th>Datum</th>
                <th>Zeit</th>
                <th>Fach</th>
                <th>Raum</th>
                <th>Schüleranzahl</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['lessons'] as $lesson)
                <tr>
                    <td>{{ $lesson['date'] }}</td>
                    <td>{{ $lesson['start_time'] }} - {{ $lesson['end_time'] }} Uhr</td>
                    <td>{{ $lesson['subject'] }}</td>
                    <td>{{ $lesson['room'] }}</td>
                    <td>{{ $lesson['student_count'] }} Schüler</td>
                    <td>
                        <span class="badge status-{{ strtolower($lesson['status']) }}">
                            {{ $lesson['status'] === 'scheduled' ? 'Geplant' : ($lesson['status'] === 'completed' ? 'Abgeschlossen' : ($lesson['status'] === 'cancelled' ? 'Abgesagt' : $lesson['status'])) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b;">Keine Unterrichtseinheiten im ausgewählten Zeitraum gefunden.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Erstellt am: {{ $data['generated_at'] }} | {{ $center['name'] ?? 'Nachhilfe Management System' }}
    </div>
</body>
</html>
