<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantInvitation extends Model
{
    use HasFactory, Concerns\HasUtcExpiries;

    protected $fillable = [
        'tenant_id', 'email', 'role', 'token', 'invited_by', 'accepted_at', 'expires_at',
    ];

    protected $casts = ['accepted_at' => 'datetime', 'expires_at' => 'datetime'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isValid(): bool
    {
        return is_null($this->accepted_at) && $this->utcFuture('expires_at');
    }
}
