<?php

return [
    'Nachhilfe' => [
        'enabled'  => true,
        'provider' => App\Modules\Nachhilfe\Providers\NachhilfeServiceProvider::class,
    ],
    'Beauty' => [
        'enabled'  => false,
        'provider' => App\Modules\Beauty\Providers\BeautyServiceProvider::class,
    ],
    'Restaurant' => [
        'enabled'  => false,
        'provider' => App\Modules\Restaurant\Providers\RestaurantServiceProvider::class,
    ],
    'Billing' => [
        'enabled'  => false,
        'provider' => App\Modules\Billing\Providers\BillingServiceProvider::class,
    ],
];
