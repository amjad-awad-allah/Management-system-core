<?php

namespace App\Modules\Nachhilfe\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoucherLowHours
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $packageId,
        public float $hoursRemaining
    ) {}
}
