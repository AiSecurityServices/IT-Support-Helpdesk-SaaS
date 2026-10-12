<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    protected function sameTenant(User $user, Ticket $ticket): bool
    {
        if ($user->is_super_admin && session('platform_context', false)) {
            return true;
        }

        // IDs may arrive as strings (PDO/MySQL) — compare as integers.
        return (int) $user->tenant_id !== 0 && (int) $user->tenant_id === (int) $ticket->tenant_id;
    }

    public function viewAny(User $user): bool
    {
        // Staff use the workspace queue; customers use the dedicated portal.
        return $user->isStaff();
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if (! $this->sameTenant($user, $ticket)) {
            return false;
        }
        if ($user->isCustomer()) {
            return (int) $ticket->customer_id === (int) $user->customer_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->isStaff() || $user->isCustomer();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if (! $this->sameTenant($user, $ticket)) {
            return false;
        }
        if ($user->isCustomer()) {
            return false; // customers reply via dedicated action, never edit
        }

        return true;
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        if (! $this->sameTenant($user, $ticket)) {
            return false;
        }
        if ($user->isCustomer()) {
            return (int) $ticket->customer_id === (int) $user->customer_id
                && ! in_array($ticket->status, ['closed'], true);
        }

        return $user->isStaff();
    }

    public function manageAssignment(User $user, Ticket $ticket): bool
    {
        return $this->sameTenant($user, $ticket)
            && $user->hasRole('owner', 'admin', 'manager');
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $this->sameTenant($user, $ticket) && $user->hasRole('owner', 'admin');
    }
}
