<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (empty($model->tenant_id) && app()->bound('currentTenantId') && app('currentTenantId')) {
                $model->tenant_id = app('currentTenantId');
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /** Query without tenant scoping (authorised platform use only). */
    public static function withoutTenantScope()
    {
        return static::withoutGlobalScope(TenantScope::class);
    }
}
