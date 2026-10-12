<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEndpoint extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['tenant_id', 'url', 'events', 'secret', 'is_active'];

    protected $casts = ['events' => 'array', 'is_active' => 'boolean'];

    protected $hidden = ['secret'];

    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }
}
