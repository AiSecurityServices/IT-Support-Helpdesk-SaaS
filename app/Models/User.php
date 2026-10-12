<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLES = ['owner', 'admin', 'manager', 'technician', 'customer'];

    public const STAFF_ROLES = ['owner', 'admin', 'manager', 'technician'];

    protected $fillable = [
        'name', 'email', 'password', 'tenant_id', 'role', 'is_super_admin',
        'is_service_account', 'phone', 'job_title', 'avatar_path', 'timezone',
        'locale', 'customer_id', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token', 'totp_secret'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_super_admin' => 'boolean',
        'is_service_account' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'totp_enabled_at' => 'datetime',
        'tenant_id' => 'integer',
        'customer_id' => 'integer',
    ];

    // ---- Relationships ----
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class)->withPivot('role')->withTimestamps();
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'technician_id');
    }

    // ---- Role helpers ----
    public function isStaff(): bool
    {
        return in_array($this->role, self::STAFF_ROLES, true);
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function hasRole(string ...$roles): bool
    {
        if ($this->is_super_admin && session('platform_context', false)) {
            return true;
        }

        return in_array($this->role, $roles, true);
    }

    public function canManage(string $area): bool
    {
        $matrix = [
            'organisation' => ['owner'],
            'billing' => ['owner'],
            'configuration' => ['owner', 'admin'],
            'teams' => ['owner', 'admin', 'manager'],
            'reports' => ['owner', 'admin', 'manager'],
            'customers' => ['owner', 'admin', 'manager', 'technician'],
            'invoices' => ['owner', 'admin', 'manager'],
        ];

        return $this->hasRole(...($matrix[$area] ?? []));
    }

    public function avatarUrl(): string
    {
        if ($this->avatar_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->avatar_path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_path);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=2563eb&color=fff';
    }
}
