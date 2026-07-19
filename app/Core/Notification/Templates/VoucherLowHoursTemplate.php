<?php

namespace App\Core\Notification\Templates;

use App\Core\Models\User;

class VoucherLowHoursTemplate implements NotificationTemplateInterface
{
    public function generate(User $user, array $data): array
    {
        // Check language preference
        $lang = $data['locale'] ?? 'de';
        
        $studentName = $data['student_name'] ?? 'das Kind';
        $subjectName = $data['subject_name'] ?? 'Fach';
        $hoursRemaining = $data['hours_remaining'] ?? 0;
        $voucherRef = $data['voucher_reference'] ?? 'Gutschein';

        if ($lang === 'en') {
            $title = 'Low Voucher Balance Warning';
            $message = "Hello, the remaining hours for the voucher {$voucherRef} ({$subjectName}) of {$studentName} is only {$hoursRemaining} hours. Please apply for a renewal soon.";
        } else {
            $title = 'Warnung: Wenig Guthaben übrig';
            $message = "Hallo, das Restguthaben für den Gutschein {$voucherRef} ({$subjectName}) von {$studentName} beträgt nur noch {$hoursRemaining} Stunden. Bitte rechtzeitig eine Verlängerung einreichen.";
        }

        return [
            'title' => $title,
            'message' => $message,
            'metadata' => [
                'student_name' => $studentName,
                'subject_name' => $subjectName,
                'hours_remaining' => (float)$hoursRemaining,
                'voucher_reference' => $voucherRef,
                'student_id' => $data['student_id'] ?? null,
                'package_id' => $data['package_id'] ?? null,
            ]
        ];
    }
}
