<?php

return [
    'title' => 'Stundenplan & Unterricht',
    'single' => 'Unterrichtsstunde',
    'subject' => 'Schulfach',
    'room' => 'Unterrichtsraum',
    'status' => [
        'scheduled' => 'Geplant',
        'completed' => 'Abgeschlossen',
        'cancelled' => 'Abgesagt',
        'rescheduled' => 'Verschoben',
    ],
    'attendance' => [
        'present' => 'Anwesend',
        'late' => 'Verspätet',
        'absent_excused' => 'Entschuldigt abwesend',
        'absent_unexcused' => 'Unentschuldigt abwesend',
    ],
    'conflict_detected' => 'Terminkonflikt festgestellt: Raum oder Lehrkraft bereits belegt.',
    'room_capacity_exceeded' => 'Die maximale Raumkapazität von :max Schülern wurde überschritten.',
    'booked_success' => 'Unterrichtsstunde erfolgreich gebucht.',
    'attendance_marked_success' => 'Anwesenheit erfolgreich erfasst.',
];
