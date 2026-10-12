<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemoteSession extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'ticket_id', 'asset_id', 'technician_id', 'provider',
        'join_url', 'purpose', 'customer_instructions', 'consent_confirmed',
        'status', 'expires_at', 'completed_at',
    ];

    protected $casts = [
        'consent_confirmed' => 'boolean',
        'expires_at' => 'datetime', 'completed_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
