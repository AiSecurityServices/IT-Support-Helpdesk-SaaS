<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    use BelongsToTenant, HasFactory;

    public const TRIGGERS = [
        'ticket.created', 'ticket.updated', 'priority.changed', 'sla.warning',
        'sla.breach', 'ticket.reassigned', 'customer.replied',
        'warranty.expiring', 'invoice.overdue', 'approval.decided',
    ];

    protected $fillable = [
        'tenant_id', 'name', 'trigger', 'conditions', 'actions',
        'is_active', 'max_runs_per_hour',
    ];

    protected $casts = ['conditions' => 'array', 'actions' => 'array', 'is_active' => 'boolean'];

    public function runs()
    {
        return $this->hasMany(AutomationRun::class);
    }
}
