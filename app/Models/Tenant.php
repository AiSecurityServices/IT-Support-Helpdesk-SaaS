<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'status', 'email', 'phone', 'timezone',
        'locale', 'currency', 'settings', 'trial_ends_at', 'suspended_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (empty($tenant->slug)) {
                $base = Str::slug($tenant->name) ?: 'tenant';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $tenant->slug = $slug;
            }
        });
    }

    // ---- Relationships ----
    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->whereIn('status', ['trial', 'active', 'past_due', 'grace'])->latestOfMany();
    }

    public function invitations()
    {
        return $this->hasMany(TenantInvitation::class);
    }

    // ---- Settings helpers (tenant-specific overrides) ----
    public function setting(string $key, $default = null)
    {
        return data_get($this->settings ?? [], $key, $default);
    }

    public function updateSettings(array $values): void
    {
        $this->settings = array_replace_recursive($this->settings ?? [], $values);
        $this->save();
    }

    /** Working-hours calendar used by the SLA engine. */
    public function workingCalendar(): array
    {
        return $this->setting('working_hours', [
            'days' => [1, 2, 3, 4, 5],
            'start' => '09:00',
            'end' => '17:30',
            'timezone' => $this->timezone ?? config('branding.default_timezone'),
        ]);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function subscriptionBlocked(): bool
    {
        return in_array($this->status, ['suspended', 'cancelled'], true);
    }
}
