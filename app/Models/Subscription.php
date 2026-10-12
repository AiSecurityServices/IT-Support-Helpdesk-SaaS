<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory, Concerns\HasUtcExpiries;

    protected $fillable = [
        'tenant_id', 'plan_id', 'status', 'interval', 'trial_ends_at',
        'current_period_start', 'current_period_end', 'grace_ends_at',
        'cancelled_at', 'provider', 'provider_customer_id', 'provider_subscription_id',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime', 'current_period_start' => 'datetime',
        'current_period_end' => 'datetime', 'grace_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function events()
    {
        return $this->hasMany(BillingEvent::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['trial', 'active', 'past_due', 'grace'], true);
    }

    public function onTrial(): bool
    {
        return $this->status === 'trial' && $this->utcFuture('trial_ends_at');
    }
}
