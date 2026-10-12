<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCatalogItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'name', 'description', 'form_schema', 'team_id',
        'sla_policy_id', 'requires_approval', 'approver_role', 'is_active',
    ];

    protected $casts = [
        'form_schema' => 'array', 'requires_approval' => 'boolean', 'is_active' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function requests()
    {
        return $this->hasMany(ServiceRequest::class);
    }
}
