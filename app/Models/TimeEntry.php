<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'ticket_id', 'technician_id', 'description', 'minutes',
        'billable', 'hourly_rate', 'started_at', 'ended_at', 'status', 'invoice_id',
    ];

    protected $casts = [
        'billable' => 'boolean',
        'started_at' => 'datetime', 'ended_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function cost(): float
    {
        return round(($this->minutes / 60) * (float) ($this->hourly_rate ?? 0), 2);
    }
}
