<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Application Locale
    |--------------------------------------------------------------------------
    |
    | The default locale determines the default language that will be used
    | by the translation service provider. This is set to German ('de').
    |
    */
    'default_locale' => env('APP_LOCALE', 'de'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Application Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. English ('en') is our robust fallback language.
    |
    */
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Supported Application Locales Registry
    |--------------------------------------------------------------------------
    |
    | Whitelist of active supported locales and their metadata.
    | Future languages (like Arabic 'ar' or Turkish 'tr') can be seamlessly
    | plugged in here without altering the core localization engine.
    |
    */
    'supported_locales' => [
        'de' => [
            'name' => 'German',
            'native' => 'Deutsch',
            'dir' => 'ltr',
        ],
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'dir' => 'ltr',
        ],
        // Future extensions:
        // 'ar' => [
        //     'name' => 'Arabic',
        //     'native' => 'العربية',
        //     'dir' => 'rtl',
        // ],
        // 'tr' => [
        //     'name' => 'Turkish',
        //     'native' => 'Türkçe',
        //     'dir' => 'ltr',
        // ],
    ],
];
