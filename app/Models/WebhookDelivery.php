<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_endpoint_id', 'event', 'payload', 'status_code', 'delivered', 'attempts',
    ];

    protected $casts = ['payload' => 'array', 'delivered' => 'boolean'];
}
