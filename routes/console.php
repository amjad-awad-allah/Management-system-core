<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Modules\Nachhilfe\Presentation\Console\CheckPackageAlertsCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule package alerts check daily
Schedule::command('nachhilfe:check-package-alerts')->daily();
