<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Automatically scopes tenant-owned records to the current tenant and
 * stamps tenant_id on creation. Super admins (platform context) bypass it.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->runningInConsole() && ! app()->bound('currentTenantId')) {
            return;
        }
        // Super admins operating in platform context see everything.
        if (Auth::check() && Auth::user()->is_super_admin && session('platform_context', false)) {
            return;
        }
        $tenantId = app()->bound('currentTenantId') ? app('currentTenantId') : null;
        if ($tenantId) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }
}
