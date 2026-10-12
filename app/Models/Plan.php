<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'price_monthly', 'price_annual', 'currency',
        'technician_seats', 'asset_limit', 'ticket_limit_monthly',
        'storage_mb', 'features', 'is_active', 'trial_eligible', 'sort_order',
    ];

    protected $casts = ['features' => 'array', 'is_active' => 'boolean', 'trial_eligible' => 'boolean'];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? [], true);
    }
}
