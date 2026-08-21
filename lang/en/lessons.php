<?php

return [
    'title' => 'Calendar & Lessons',
    'single' => 'Lesson',
    'subject' => 'Subject',
    'room' => 'Classroom',
    'status' => [
        'scheduled' => 'Scheduled',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'rescheduled' => 'Rescheduled',
    ],
    'attendance' => [
        'present' => 'Present',
        'late' => 'Late',
        'absent_excused' => 'Absent (Excused)',
        'absent_unexcused' => 'Absent (Unexcused)',
    ],
    'conflict_detected' => 'Schedule conflict detected: Room or teacher already booked.',
    'room_capacity_exceeded' => 'Maximum room capacity of :max students exceeded.',
    'booked_success' => 'Lesson booked successfully.',
    'attendance_marked_success' => 'Attendance marked successfully.',
];
