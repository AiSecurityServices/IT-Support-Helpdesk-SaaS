<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImpersonationGrant extends Model
{
    use HasFactory, Concerns\HasUtcExpiries;

    protected $fillable = [
        'tenant_id', 'target_user_id', 'granted_by', 'reason',
        'expires_at', 'used_at', 'ended_at', 'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime', 'used_at' => 'datetime',
        'ended_at' => 'datetime', 'revoked_at' => 'datetime',
    ];

    public function target()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function granter()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function isUsable(): bool
    {
        return is_null($this->revoked_at) && is_null($this->ended_at)
            && $this->utcFuture('expires_at');
    }
}
