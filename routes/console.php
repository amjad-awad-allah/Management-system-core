<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('nachhilfe:expire-packages', function () {
    $expiredPackages = \App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage::whereIn('status', ['active', 'pending_approval'])
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now()->toDateString())
        ->get();

    foreach ($expiredPackages as $package) {
        $package->status = 'expired';
        $package->save();
    }

    $this->info("Expired " . $expiredPackages->count() . " hour approvals.");
})->purpose('Transition expired student tutoring approvals/vouchers to expired status');

\Illuminate\Support\Facades\Schedule::command('nachhilfe:expire-packages')->daily();
