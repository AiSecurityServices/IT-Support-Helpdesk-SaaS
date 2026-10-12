<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'actor_id', 'action', 'subject_type', 'subject_id', 'meta', 'ip_address',
    ];

    protected $casts = ['meta' => 'array'];

    protected static function booted(): void
    {
        // Append-only: audit records may never be altered or removed through
        // the application. Corrections happen via new compensating entries.
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public static function record(?int $tenantId, ?int $actorId, string $action, $subject = null, array $meta = []): void
    {
        static::create([
            'tenant_id' => $tenantId,
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject->id ?? null,
            'meta' => $meta ?: null,
            'ip_address' => request()->ip(),
        ]);
    }
}
