<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Stundennachweis Lernförderung</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 11pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 25px;
            border-bottom: 2px solid #581c87;
            padding-bottom: 15px;
        }
        .header-title {
            font-size: 20pt;
            font-weight: bold;
            color: #581c87;
            margin: 0;
        }
        .header-subtitle {
            font-size: 11pt;
            color: #666;
            margin: 5px 0 0 0;
        }
        .logo-img {
            max-height: 50px;
            max-width: 170px;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 6px 10px;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            width: 25%;
        }
        .info-value {
            border-bottom: 1px dotted #ccc;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .attendance-table th, .attendance-table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            text-align: left;
            font-size: 9.5pt;
        }
        .attendance-table th {
            background-color: #f3e8ff;
            color: #581c87;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .signature-cell {
            height: 35px;
            width: 130px;
        }
        .footer-summary {
            margin-top: 20px;
            margin-bottom: 40px;
            font-size: 11pt;
            font-weight: bold;
            text-align: right;
            padding-right: 15px;
        }
        .footer-summary span {
            color: #581c87;
            font-size: 14pt;
        }
        .signature-section {
            width: 100%;
            margin-top: 50px;
            border-collapse: collapse;
        }
        .signature-box {
            width: 45%;
            text-align: center;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 9pt;
            color: #666;
        }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td>
                    <h1 class="header-title">STUNDENNACHWEIS</h1>
                    <p class="header-subtitle">Lernförderung &mdash; Bildung und Teilhabe (BuT)</p>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    @if(!empty($center['logo_base64']))
                        <img src="{{ $center['logo_base64'] }}" alt="Logo" class="logo-img" /><br>
                    @endif
                    <strong style="font-size: 12pt; color: #581c87;">{{ $center['name'] ?? 'Muster Nachhilfeinstitut' }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-grid">
        <tr>
            <td class="info-label">Schüler/in:</td>
            <td class="info-value">{{ $student->first_name }} {{ $student->last_name }}</td>
            <td class="info-label">Geburtsdatum:</td>
            <td class="info-value">
                {{ $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d.m.Y') : 'N/A' }}
            </td>
        </tr>
        <tr>
            <td class="info-label">Abrechnungsmonat:</td>
            <td class="info-value">{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</td>
            <td class="info-label">Voucher / Aktenzeichen:</td>
            <td class="info-value">
                @php
                    $jcPackages = $student->packages->where('funding_source', 'jobcenter')->whereNotNull('voucher_reference');
                    $voucherRef = $jcPackages->first()?->voucher_reference ?? 'N/A';
                @endphp
                {{ $voucherRef }}
            </td>
        </tr>
    </table>

    <table class="attendance-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">Nr.</th>
                <th style="width: 15%;">Datum</th>
                <th style="width: 20%;">Uhrzeit</th>
                <th style="width: 15%;" class="text-center">Stunden (Std.)</th>
                <th style="width: 15%;">Fach</th>
                <th style="width: 15%;">Lehrkraft</th>
                <th style="width: 20%;" class="text-center">Unterschrift Schüler/in</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $index => $attendance)
                @php
                    $lesson = $attendance->lessonStudent?->lesson;
                    $durationHours = $lesson ? round($lesson->duration_minutes / 60, 2) : 0;
                    $startTime = $lesson ? \Carbon\Carbon::parse($lesson->start_time)->format('H:i') : '';
                    $endTime = $lesson ? \Carbon\Carbon::parse($lesson->end_time)->format('H:i') : '';
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $lesson ? \Carbon\Carbon::parse($lesson->date)->format('d.m.Y') : 'N/A' }}</td>
                    <td>{{ $startTime }} &ndash; {{ $endTime }}</td>
                    <td class="text-center">{{ number_format($durationHours, 2) }}</td>
                    <td>{{ $lesson?->subject?->name ?? 'N/A' }}</td>
                    <td>{{ $lesson?->teacher?->name ?? 'N/A' }}</td>
                    <td class="signature-cell"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #666;">
                        Keine Unterrichtsstunden für diesen Zeitraum erfasst.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-summary">
        Gesamtsumme der Stunden: <span>{{ number_format($totalHours, 2) }} Std.</span>
    </div>

    <p style="font-size: 9pt; color: #555; margin-bottom: 40px; border-top: 1px solid #eee; padding-top: 10px;">
        Hiermit wird bestätigt, dass der oben genannte Unterricht ordnungsgemäß stattgefunden hat.
    </p>

    <table class="signature-section">
        <tr>
            <td class="signature-box">
                <div class="signature-line">
                    Ort, Datum / Unterschrift d. gesetzl. Vertreters o. Schülers
                </div>
            </td>
            <td style="width: 10%;"></td>
            <td class="signature-box">
                <div class="signature-line">
                    {{ $center['name'] ?? 'Muster Nachhilfeinstitut' }} / Stempel & Unterschrift
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
