<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'subscription_id', 'type', 'amount', 'currency',
        'provider_event_id', 'payload',
    ];

    protected $casts = ['payload' => 'array'];
}
