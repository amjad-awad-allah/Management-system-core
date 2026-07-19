<?php

namespace App\Modules\Nachhilfe\Presentation\Console;

use Illuminate\Console\Command;
use App\Modules\Nachhilfe\Application\Services\PackageAlertService;

class CheckPackageAlertsCommand extends Command
{
    protected $signature = 'nachhilfe:check-package-alerts';

    protected $description = 'Scan student packages for expiring dates and low hour balances to dispatch core alerts';

    public function handle(PackageAlertService $alertService): int
    {
        $this->info('Starting automated voucher and package alerts check...');
        
        $alertService->checkPackageAlerts();
        $this->info('Voucher alert checks completed.');

        $alertService->checkPackageExpired();
        $this->info('Voucher expiration status check completed.');

        return 0;
    }
}
