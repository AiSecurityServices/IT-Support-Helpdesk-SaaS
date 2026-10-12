<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUSES = ['draft', 'pending_approval', 'approved', 'rejected', 'in_progress', 'fulfilled', 'cancelled'];

    protected $fillable = [
        'tenant_id', 'number', 'service_catalog_item_id', 'requester_id',
        'customer_id', 'ticket_id', 'asset_id', 'status', 'form_data',
        'approvals', 'completion_evidence', 'customer_confirmed',
    ];

    protected $casts = [
        'form_data' => 'array', 'approvals' => 'array', 'customer_confirmed' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (ServiceRequest $r) {
            if (empty($r->number) && $r->tenant_id) {
                $max = static::withoutTenantScope()->where('tenant_id', $r->tenant_id)->max('id');
                $r->number = 'REQ-'.str_pad((int) $max + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function item()
    {
        return $this->belongsTo(ServiceCatalogItem::class, 'service_catalog_item_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function tasks()
    {
        return $this->hasMany(ServiceTask::class);
    }
}
