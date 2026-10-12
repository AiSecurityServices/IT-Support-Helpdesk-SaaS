<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'name', 'description', 'targets', 'working_hours',
        'pause_on_pending_customer', 'is_default', 'is_active',
    ];

    protected $casts = [
        'targets' => 'array', 'working_hours' => 'array',
        'pause_on_pending_customer' => 'boolean',
        'is_default' => 'boolean', 'is_active' => 'boolean',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'sla_policy_id');
    }

    public function targetFor(string $priority, string $target): ?int
    {
        return $this->targets[$priority][$target]
            ?? config("supportflow.sla_default_targets.{$priority}.{$target}");
    }
}
