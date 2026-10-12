<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'asset_tag', 'name', 'type', 'manufacturer', 'model',
        'serial_number', 'computer_name', 'operating_system', 'os_version',
        'ip_address', 'customer_id', 'location_id', 'assigned_to_user_id',
        'department', 'purchase_date', 'supplier', 'purchase_cost',
        'warranty_expires_at', 'status', 'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date', 'warranty_expires_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Asset $asset) {
            if (empty($asset->asset_tag) && $asset->tenant_id) {
                $max = static::withoutTenantScope()->where('tenant_id', $asset->tenant_id)->max('id');
                $asset->asset_tag = 'AST-'.str_pad((int) $max + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function history()
    {
        return $this->hasMany(AssetHistory::class)->orderByDesc('created_at');
    }

    public function warrantyDaysLeft(): ?int
    {
        if (! $this->warranty_expires_at) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->warranty_expires_at, false);
    }
}
