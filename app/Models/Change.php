<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Change extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'changes';

    public const STATUSES = ['draft', 'pending_approval', 'approved', 'rejected', 'scheduled', 'implementing', 'implemented', 'failed', 'closed'];

    protected $fillable = [
        'tenant_id', 'title', 'type', 'status', 'impact', 'risk',
        'implementation_plan', 'test_plan', 'rollback_plan',
        'window_start', 'window_end', 'approvals', 'outcome', 'requester_id',
    ];

    protected $casts = [
        'window_start' => 'datetime', 'window_end' => 'datetime', 'approvals' => 'array',
    ];

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'change_ticket');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
}
