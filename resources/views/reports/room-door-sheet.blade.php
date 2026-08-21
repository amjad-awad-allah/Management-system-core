<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Raumbelegung - {{ $data['room_name'] }}</title>
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
            border-bottom: 2px solid #7c3aed;
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
            color: #5b21b6;
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
        table.room-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.room-table th, table.room-table td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
        }
        table.room-table th {
            background-color: #f5f3ff;
            color: #5b21b6;
            font-weight: bold;
        }
        table.room-table tr:nth-child(even) {
            background-color: #fcfaff;
        }
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
                <h1>Raumbelegungsplan (Tagesübersicht)</h1>
                <div class="meta">
                    <strong>Raum:</strong> {{ $data['room_name'] }} |
                    <strong>Datum:</strong> {{ $data['date'] }}
                    @if(!empty($data['room_capacity']))
                        | <strong>Kapazität:</strong> {{ $data['room_capacity'] }} Plätze
                    @endif
                </div>
            </td>
            <td style="text-align: right;">
                @if(!empty($center['logo_base64']))
                    <img src="{{ $center['logo_base64'] }}" alt="Logo" class="logo-img" /><br>
                @endif
                <strong style="font-size: 12px; color: #5b21b6;">{{ $center['name'] ?? 'Muster Nachhilfeinstitut' }}</strong>
            </td>
        </tr>
    </table>

    <table class="room-table">
        <thead>
            <tr>
                <th>Zeitraum</th>
                <th>Fach</th>
                <th>Lehrkraft</th>
                <th>Schüleranzahl</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['lessons'] as $lesson)
                <tr>
                    <td>{{ $lesson['start_time'] }} - {{ $lesson['end_time'] }} Uhr</td>
                    <td>{{ $lesson['subject'] }}</td>
                    <td>{{ $lesson['teacher_name'] }}</td>
                    <td>{{ $lesson['student_count'] }} Schüler</td>
                    <td>
                        {{ $lesson['status'] === 'scheduled' ? 'Geplant' : ($lesson['status'] === 'completed' ? 'Abgeschlossen' : ($lesson['status'] === 'cancelled' ? 'Abgesagt' : $lesson['status'])) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b;">Keine Belegungen an diesem Tag geplant.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Erstellt am: {{ $data['generated_at'] }} | {{ $center['name'] ?? 'Nachhilfe Management System' }}
    </div>
</body>
</html>
