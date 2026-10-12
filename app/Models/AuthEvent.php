<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthEvent extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'tenant_id', 'event', 'ip_address', 'user_agent', 'meta'];

    protected $casts = ['meta' => 'array'];

    public static function record(?User $user, string $event, array $meta = []): void
    {
        try {
            static::create([
                'user_id' => $user?->id,
                'tenant_id' => $user?->tenant_id,
                'event' => $event,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 512),
                'meta' => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
