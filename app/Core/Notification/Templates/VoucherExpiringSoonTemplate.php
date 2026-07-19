<?php

namespace App\Core\Notification\Templates;

use App\Core\Models\User;

class VoucherExpiringSoonTemplate implements NotificationTemplateInterface
{
    public function generate(User $user, array $data): array
    {
        $lang = $data['locale'] ?? 'de';
        
        $studentName = $data['student_name'] ?? 'das Kind';
        $subjectName = $data['subject_name'] ?? 'Fach';
        $expiresAt = $data['expires_at'] ?? '';
        $daysRemaining = $data['days_remaining'] ?? 0;
        $voucherRef = $data['voucher_reference'] ?? 'Gutschein';

        if ($lang === 'en') {
            $title = 'Voucher Expiration Warning';
            $message = "Hello, the voucher {$voucherRef} ({$subjectName}) of {$studentName} will expire on {$expiresAt} ({$daysRemaining} days remaining). Please apply for a renewal soon.";
        } else {
            $title = 'Warnung: Gutschein läuft ab';
            $message = "Hallo, der Gutschein {$voucherRef} ({$subjectName}) von {$studentName} läuft am {$expiresAt} ab (noch {$daysRemaining} Tage übrig). Bitte rechtzeitig eine Verlängerung einreichen.";
        }

        return [
            'title' => $title,
            'message' => $message,
            'metadata' => [
                'student_name' => $studentName,
                'subject_name' => $subjectName,
                'expires_at' => $expiresAt,
                'days_remaining' => (int)$daysRemaining,
                'voucher_reference' => $voucherRef,
                'student_id' => $data['student_id'] ?? null,
                'package_id' => $data['package_id'] ?? null,
            ]
        ];
    }
}
