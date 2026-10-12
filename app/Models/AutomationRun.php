<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationRun extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'automation_rule_id', 'tenant_id', 'subject_id', 'status', 'detail', 'dedupe_key',
    ];

    protected $casts = ['detail' => 'array'];
}
