<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomFieldDefinition extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'applies_to', 'label', 'key', 'type', 'options',
        'is_required', 'is_active',
    ];

    protected $casts = ['options' => 'array', 'is_required' => 'boolean', 'is_active' => 'boolean'];
}
