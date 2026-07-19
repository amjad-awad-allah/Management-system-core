<?php

namespace App\Modules\Nachhilfe\Application\Listeners;

use App\Modules\Nachhilfe\Domain\Events\VoucherExpiringSoon;
use App\Modules\Nachhilfe\Domain\Events\VoucherLowHours;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use App\Core\Notification\Services\NotificationService;
use App\Core\Notification\Data\NotificationMessage;
use App\Core\Notification\Data\NotificationOptions;
use App\Core\Notification\Templates\VoucherLowHoursTemplate;
use App\Core\Notification\Templates\VoucherExpiringSoonTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;

class VoucherAlertListener implements ShouldQueue
{
    private NotificationService $notificationService;
    private VoucherLowHoursTemplate $lowHoursTemplate;
    private VoucherExpiringSoonTemplate $expiringSoonTemplate;

    public function __construct(
        NotificationService $notificationService,
        VoucherLowHoursTemplate $lowHoursTemplate,
        VoucherExpiringSoonTemplate $expiringSoonTemplate
    ) {
        $this->notificationService = $notificationService;
        $this->lowHoursTemplate = $lowHoursTemplate;
        $this->expiringSoonTemplate = $expiringSoonTemplate;
    }

    public function handleLowHours(VoucherLowHours $event): void
    {
        $package = StudentPackage::with(['student.user', 'subject'])->find($event->packageId);
        if (!$package || !$package->student || !$package->student->user) {
            return;
        }

        $student = $package->student;
        $parent = $student->user;

        // Compile template
        $templateData = [
            'locale' => $parent->locale ?? 'de',
            'student_name' => $student->first_name . ' ' . $student->last_name,
            'subject_name' => $package->subject?->name ?? 'All Subjects',
            'hours_remaining' => $event->hoursRemaining,
            'voucher_reference' => $package->voucher_reference ?? 'Private Package',
            'student_id' => $student->id,
            'package_id' => $package->id,
            'parent_phone' => $student->parent_phone_1,
        ];

        $rendered = $this->lowHoursTemplate->generate($parent, $templateData);

        $message = new NotificationMessage(
            type: 'voucher_low_hours',
            title: $rendered['title'],
            message: $rendered['message'],
            sourceType: 'student_package',
            sourceId: $package->id,
            periodKey: 'once',
            metadata: $rendered['metadata']
        );

        $options = new NotificationOptions(
            channels: ['in_app', 'chat', 'whatsapp'],
            priority: 'high'
        );

        $this->notificationService->send($parent, $message, $options);
    }

    public function handleExpiringSoon(VoucherExpiringSoon $event): void
    {
        $package = StudentPackage::with(['student.user', 'subject'])->find($event->packageId);
        if (!$package || !$package->student || !$package->student->user) {
            return;
        }

        $student = $package->student;
        $parent = $student->user;

        // Compile template
        $templateData = [
            'locale' => $parent->locale ?? 'de',
            'student_name' => $student->first_name . ' ' . $student->last_name,
            'subject_name' => $package->subject?->name ?? 'All Subjects',
            'expires_at' => $package->expires_at ? $package->expires_at->format('Y-m-d') : '',
            'days_remaining' => $event->daysRemaining,
            'voucher_reference' => $package->voucher_reference ?? 'Private Package',
            'student_id' => $student->id,
            'package_id' => $package->id,
            'parent_phone' => $student->parent_phone_1,
        ];

        $rendered = $this->expiringSoonTemplate->generate($parent, $templateData);

        $today = now();
        $periodKey = $today->format('Y_m'); // e.g. '2026_07'

        $message = new NotificationMessage(
            type: 'voucher_expiring_soon',
            title: $rendered['title'],
            message: $rendered['message'],
            sourceType: 'student_package',
            sourceId: $package->id,
            periodKey: $periodKey,
            metadata: $rendered['metadata']
        );

        $options = new NotificationOptions(
            channels: ['in_app', 'chat', 'whatsapp'],
            priority: 'high'
        );

        $this->notificationService->send($parent, $message, $options);
    }
}
