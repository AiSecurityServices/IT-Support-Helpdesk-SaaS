<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'company_name', 'account_code', 'email', 'phone', 'website',
        'address', 'city', 'postcode', 'country', 'status', 'owner_id',
        'sla_policy_id', 'notes',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function primaryContact()
    {
        return $this->hasOne(Contact::class)->where('is_primary', true)->oldestOfMany();
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function slaPolicy()
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function portalUsers()
    {
        return $this->hasMany(User::class, 'customer_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
