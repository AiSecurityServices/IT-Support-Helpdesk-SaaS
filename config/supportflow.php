<?php

/*
|--------------------------------------------------------------------------
| SupportFlow Cloud — Operational defaults (plans, tickets, SLA, limits)
|--------------------------------------------------------------------------
| Platform administrators can override plans/limits at runtime via the
| Super Admin UI; values below are the initial seeded defaults.
*/

return [
    'plans' => [
        'starter' => [
            'name' => 'Starter', 'price_monthly' => 19.00, 'price_annual' => 190.00,
            'currency' => 'GBP', 'technician_seats' => 3, 'asset_limit' => 250,
            'ticket_limit_monthly' => 500, 'storage_mb' => 1024,
            'features' => ['tickets', 'customer_portal', 'assets_basic', 'reports_basic'],
        ],
        'growth' => [
            'name' => 'Growth', 'price_monthly' => 49.00, 'price_annual' => 490.00,
            'currency' => 'GBP', 'technician_seats' => 10, 'asset_limit' => 1500,
            'ticket_limit_monthly' => 3000, 'storage_mb' => 10240,
            'features' => ['tickets', 'customer_portal', 'assets', 'sla', 'teams', 'invoicing', 'reports', 'automation'],
        ],
        'professional' => [
            'name' => 'Professional', 'price_monthly' => 99.00, 'price_annual' => 990.00,
            'currency' => 'GBP', 'technician_seats' => 50, 'asset_limit' => 10000,
            'ticket_limit_monthly' => 20000, 'storage_mb' => 51200,
            'features' => ['tickets', 'customer_portal', 'assets', 'sla_advanced', 'teams', 'invoicing', 'reports_advanced', 'automation', 'api', 'webhooks', 'audit'],
        ],
    ],

    'ticket_priorities' => ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'],
    'ticket_statuses' => ['new' => 'New', 'open' => 'Open', 'pending_customer' => 'Pending Customer', 'pending_internal' => 'Pending Internal', 'resolved' => 'Resolved', 'closed' => 'Closed'],
    'ticket_sources' => ['portal' => 'Customer Portal', 'staff' => 'Staff Entry', 'email' => 'Email', 'api' => 'API'],

    // Allowed status transitions (server-side validated).
    'ticket_transitions' => [
        'new' => ['open', 'pending_internal', 'resolved', 'closed'],
        'open' => ['pending_customer', 'pending_internal', 'resolved', 'closed'],
        'pending_customer' => ['open', 'resolved', 'closed'],
        'pending_internal' => ['open', 'pending_customer', 'resolved', 'closed'],
        'resolved' => ['open', 'closed'],
        'closed' => ['open'],
    ],

    'sla_default_targets' => [ // minutes
        'urgent' => ['first_response' => 30, 'resolution' => 240],
        'high' => ['first_response' => 120, 'resolution' => 960],
        'normal' => ['first_response' => 480, 'resolution' => 2880],
        'low' => ['first_response' => 1440, 'resolution' => 7200],
    ],
    'sla_warning_threshold' => 0.8, // warn at 80% of target elapsed

    'asset_statuses' => ['in_stock' => 'In Stock', 'assigned' => 'Assigned', 'in_repair' => 'In Repair', 'retired' => 'Retired', 'disposed' => 'Disposed'],
    'invoice_statuses' => ['draft' => 'Draft', 'issued' => 'Issued', 'partially_paid' => 'Partially Paid', 'paid' => 'Paid', 'overdue' => 'Overdue', 'void' => 'Void'],

    'upload' => [
        'max_kb' => (int) env('SUPPORTFLOW_UPLOAD_MAX_KB', 10240),
        'allowed_mimes' => explode(',', env('SUPPORTFLOW_UPLOAD_MIMES', 'jpg,jpeg,png,gif,webp,pdf,txt,csv,log,zip,doc,docx,xls,xlsx')),
    ],

    'import' => ['max_rows' => 2000, 'max_kb' => 5120],
    'avatar' => ['max_kb' => 2048],

    'roles' => ['owner', 'admin', 'manager', 'technician', 'customer'],
    'staff_roles' => ['owner', 'admin', 'manager', 'technician'],

    'password_min_length' => 10,
    'invoice_prefix' => 'INV-',
    'ticket_prefix' => 'TKT-',
];
