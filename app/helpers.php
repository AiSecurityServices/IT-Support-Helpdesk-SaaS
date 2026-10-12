<?php

use App\Models\AuditLog;

if (! function_exists('currentTenant')) {
    function currentTenant(): ?App\Models\Tenant
    {
        return app()->bound('currentTenant') ? app('currentTenant') : null;
    }
}

if (! function_exists('currentTenantId')) {
    function currentTenantId(): ?int
    {
        return app()->bound('currentTenantId') ? app('currentTenantId') : null;
    }
}

if (! function_exists('tenant_setting')) {
    function tenant_setting(string $key, $default = null)
    {
        $tenant = currentTenant();

        return $tenant ? $tenant->setting($key, $default) : $default;
    }
}

if (! function_exists('audit')) {
    /** Record a sensitive/important action in the audit log. */
    function audit(string $action, $subject = null, array $meta = []): void
    {
        try {
            // Impersonated sessions attribute the real operator alongside the target.
            if (session()->has('impersonate.original_id')) {
                $meta['impersonated_by'] = session('impersonate.original_id');
                $meta['impersonation_grant'] = session('impersonate.grant_id');
            }
            if ($requestId = requestId()) {
                $meta['request_id'] = $requestId;
            }
            AuditLog::record(currentTenantId(), auth()->id(), $action, $subject, $meta);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

if (! function_exists('money')) {
    /**
     * Display formatting only — arithmetic stays in decimal(12,2) minor units.
     * Policy: amounts are stored with 2 decimals for ALL currencies (including
     * zero-decimal ones like JPY); display rounds per currency. No cross-currency
     * maths is ever performed without an explicit conversion (none configured).
     */
    function money($amount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?? tenant_setting('invoice.currency', config('branding.default_currency', 'GBP')));
        $symbols = [
            'GBP' => '£', 'USD' => '$', 'EUR' => '€', 'CHF' => 'CHF ', 'SEK' => 'kr ',
            'NOK' => 'kr ', 'DKK' => 'kr ', 'AUD' => 'A$', 'CAD' => 'C$', 'INR' => '₹',
            'PKR' => '₨', 'BDT' => '৳', 'AED' => 'د.إ ', 'SAR' => '﷼ ', 'JPY' => '¥',
            'KRW' => '₩', 'HUF' => 'Ft ',
        ];
        $zeroDecimal = ['JPY', 'KRW', 'HUF'];
        $decimals = in_array($currency, $zeroDecimal, true) ? 0 : 2;
        $symbol = $symbols[$currency] ?? ($currency.' ');

        return $symbol.number_format((float) $amount, $decimals, '.', ',');
    }
}

if (! function_exists('brand')) {
    function brand(string $key, $default = null)
    {
        return config('branding.'.$key, $default);
    }
}

if (! function_exists('feature')) {
    /**
     * System-wide feature flags with per-tenant override.
     * Global default from config/features.php; tenant settings win.
     */
    function feature(string $key): bool
    {
        $tenant = currentTenant();
        if ($tenant) {
            $override = $tenant->setting('features.'.$key, null);
            if ($override !== null) {
                return (bool) $override;
            }
        }

        return (bool) config('features.'.$key, true);
    }
}

if (! function_exists('tenant_cache_key')) {
    /**
     * Tenant-namespaced cache keys. Shared cache backends must never let
     * one tenant read or poison another tenant's entries.
     */
    function tenant_cache_key(string $key, ?int $tenantId = null): string
    {
        $tenantId ??= currentTenantId() ?? 0;

        return "tenant:{$tenantId}:{$key}";
    }
}

if (! function_exists('requestId')) {
    function requestId(): ?string
    {
        return app()->bound('requestId') ? app('requestId') : null;
    }
}

if (! function_exists('csv_escape')) {
    /** Neutralise spreadsheet formula injection in CSV exports. */
    function csv_escape($value): string
    {
        $value = (string) $value;
        if (preg_match('/^[\s]*[=+\-@\t\r]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
