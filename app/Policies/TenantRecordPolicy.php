<?php

namespace App\Policies;

use App\Models\User;

class TenantRecordPolicy
{
    protected function sameTenant(User $user, $record): bool
    {
        if ($user->is_super_admin && session('platform_context', false)) {
            return true;
        }

        return isset($record->tenant_id) && $user->tenant_id && (int) $user->tenant_id === (int) $record->tenant_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, $record): bool
    {
        if (! $this->sameTenant($user, $record)) {
            return false;
        }
        // Customer portal users: only their own customer organisation.
        if ($user->isCustomer()) {
            $customerId = $record->customer_id ?? ($record instanceof \App\Models\Customer ? $record->id : null);
            if ($record instanceof \App\Models\Invoice) {
                $customerId = $record->customer_id;
            }

            return $customerId && (int) $customerId === (int) $user->customer_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, $record): bool
    {
        return $this->sameTenant($user, $record) && $user->isStaff();
    }

    public function delete(User $user, $record): bool
    {
        return $this->sameTenant($user, $record) && $user->hasRole('owner', 'admin');
    }
}
