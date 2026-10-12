<?php

/*
|--------------------------------------------------------------------------
| SupportFlow Cloud — White-label branding & product configuration
|--------------------------------------------------------------------------
| Everything customer-visible (name, logo, colours, company info, currency
| and support contacts) lives here so the app can be rebranded by editing
| .env values or this file. Tenant-level overrides live in tenant settings.
*/

return [
    'product_name' => env('BRAND_PRODUCT_NAME', 'SupportFlow Cloud'),
    'company_name' => env('BRAND_COMPANY_NAME', 'SupportFlow Ltd'),
    'company_address' => env('BRAND_COMPANY_ADDRESS', '71 Shelton Street, London WC2H 9JQ, United Kingdom'),
    'company_email' => env('BRAND_COMPANY_EMAIL', 'hello@supportflow.example'),
    'company_phone' => env('BRAND_COMPANY_PHONE', '+44 20 7946 0958'),
    'support_email' => env('BRAND_SUPPORT_EMAIL', 'support@supportflow.example'),
    'logo_path' => env('BRAND_LOGO_PATH', 'img/logo.svg'),
    'logo_dark_path' => env('BRAND_LOGO_DARK_PATH', 'img/logo-dark.svg'),
    'favicon_path' => env('BRAND_FAVICON_PATH', 'img/favicon.svg'),

    // Default colour system (tenants may override accent via settings).
    'colors' => [
        'primary' => env('BRAND_COLOR_PRIMARY', '#2563eb'),   // blue-600
        'accent' => env('BRAND_COLOR_ACCENT', '#0ea5e9'),     // sky-500
        'success' => '#16a34a',
        'warning' => '#d97706',
        'danger' => '#dc2626',
    ],

    'default_currency' => env('BRAND_DEFAULT_CURRENCY', 'GBP'),
    'default_timezone' => env('BRAND_DEFAULT_TIMEZONE', 'Europe/London'),
    'default_locale' => env('BRAND_DEFAULT_LOCALE', 'en'),

    // Trial defaults for new tenants.
    'trial_days' => (int) env('BRAND_TRIAL_DAYS', 14),

    'social' => [
        'twitter' => env('BRAND_SOCIAL_TWITTER', ''),
        'linkedin' => env('BRAND_SOCIAL_LINKEDIN', ''),
        'github' => env('BRAND_SOCIAL_GITHUB', ''),
    ],

    'show_powered_by' => (bool) env('BRAND_SHOW_POWERED_BY', true),
];
