<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const OPEN_STATUSES = ['new', 'open', 'pending_customer', 'pending_internal'];

    protected $fillable = [
        'tenant_id', 'number', 'subject', 'description', 'customer_id', 'contact_id',
        'requester_id', 'category_id', 'subcategory', 'priority', 'status',
        'assigned_to', 'team_id', 'asset_id', 'source', 'tags', 'custom_fields',
        'due_at', 'sla_policy_id', 'escalation_level', 'first_response_at',
        'resolved_at', 'closed_at', 'satisfaction_rating', 'satisfaction_feedback',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'due_at' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'tenant_id' => 'integer', 'customer_id' => 'integer',
        'contact_id' => 'integer', 'requester_id' => 'integer',
        'category_id' => 'integer', 'assigned_to' => 'integer',
        'team_id' => 'integer', 'asset_id' => 'integer',
        'sla_policy_id' => 'integer', 'escalation_level' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->number) && $ticket->tenant_id) {
                $prefix = config('supportflow.ticket_prefix', 'TKT-');
                $max = static::withoutTenantScope()
                    ->where('tenant_id', $ticket->tenant_id)
                    ->max('id');
                $ticket->number = $prefix.str_pad((int) $max + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // ---- Relationships ----
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function slaPolicy()
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    public function publicMessages()
    {
        return $this->hasMany(TicketMessage::class)->where('visibility', 'public')->orderBy('created_at');
    }

    public function history()
    {
        return $this->hasMany(TicketHistory::class)->orderByDesc('created_at');
    }

    public function watchers()
    {
        return $this->belongsToMany(User::class, 'ticket_watchers');
    }

    public function slaEvents()
    {
        return $this->hasMany(SlaEvent::class)->orderBy('created_at');
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    // ---- Helpers ----
    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at && $this->due_at->isPast();
    }

    public function tagList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->tags))));
    }

    public function billableMinutes(): int
    {
        return (int) $this->timeEntries()->where('billable', true)->sum('minutes');
    }
}
