<?php

namespace App\Modules\Nachhilfe\Application\Services;

use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use App\Core\Notification\Services\NotificationService;
use App\Modules\Nachhilfe\Domain\Events\VoucherExpiringSoon;
use App\Modules\Nachhilfe\Domain\Events\VoucherLowHours;
use Carbon\Carbon;

class PackageAlertService
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Scan all packages for expiration and low hours daily alerts.
     */
    public function checkPackageAlerts(): void
    {
        $today = now();
        $fourteenDaysFromNow = $today->copy()->addDays(14)->toDateString();

        // 1. Check expiring soon packages
        $expiringPackages = StudentPackage::whereIn('status', ['active', 'pending_approval'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '>=', $today->toDateString())
            ->where('expires_at', '<=', $fourteenDaysFromNow)
            ->get();

        foreach ($expiringPackages as $package) {
            $expiresAt = Carbon::parse($package->expires_at);
            $daysRemaining = max(0, (int)$today->diffInDays($expiresAt, false));
            $periodKey = $today->format('Y_m'); // e.g. '2026_07' to allow one alert per period

            // Use decoupled helper to check for duplicate
            $alreadyAlerted = $this->notificationService->hasBeenSent(
                'student_package',
                $package->id,
                'voucher_expiring_soon',
                $periodKey
            );

            if (!$alreadyAlerted) {
                VoucherExpiringSoon::dispatch($package->id, $daysRemaining);
            }
        }

        // 2. Check low hours packages
        $lowHoursPackages = StudentPackage::whereIn('status', ['active', 'pending_approval'])
            ->where('remaining_hours', '>', 0)
            ->where('remaining_hours', '<=', 3)
            ->get();

        foreach ($lowHoursPackages as $package) {
            // Use decoupled helper to check for duplicate
            $alreadyAlerted = $this->notificationService->hasBeenSent(
                'student_package',
                $package->id,
                'voucher_low_hours',
                'once'
            );

            if (!$alreadyAlerted) {
                VoucherLowHours::dispatch($package->id, (float)$package->remaining_hours);
            }
        }
    }

    /**
     * Check expired packages and transition status daily.
     */
    public function checkPackageExpired(): void
    {
        $expiredPackages = StudentPackage::whereIn('status', ['active', 'pending_approval'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->toDateString())
            ->get();

        foreach ($expiredPackages as $package) {
            $package->status = 'expired';
            $package->save();
        }
    }

    /**
     * Check and trigger low-hour event in real-time.
     */
    public function checkAndTriggerLowHours(string $packageId): void
    {
        $package = StudentPackage::find($packageId);
        if (!$package) {
            return;
        }

        if (in_array($package->status, ['active', 'pending_approval']) && $package->remaining_hours <= 3 && $package->remaining_hours > 0) {
            $alreadyAlerted = $this->notificationService->hasBeenSent(
                'student_package',
                $package->id,
                'voucher_low_hours',
                'once'
            );

            if (!$alreadyAlerted) {
                VoucherLowHours::dispatch($package->id, (float)$package->remaining_hours);
            }
        }
    }
}
