<?php

/*
|--------------------------------------------------------------------------
| System-wide feature flags for safe staged rollout.
|--------------------------------------------------------------------------
| Global defaults live here. Tenants may override individual flags via
| workspace settings (settings UI), which take precedence. Flags gate
| navigation visibility and explicitly-flagged behavior only — core
| safety features (isolation, auth, billing verification) are never
| behind a flag.
*/

return [
    'knowledge_base' => true,
    'service_catalog' => true,
    'problems' => true,
    'changes' => true,
    'major_incidents' => true,
    'cmdb' => true,
    'automation' => true,
    'api' => true,
    'outgoing_webhooks' => true,
    'customer_portal' => true,
];
