<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaEvent extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['tenant_id', 'ticket_id', 'type', 'target', 'deadline_at', 'meta'];

    protected $casts = ['deadline_at' => 'datetime', 'meta' => 'array'];
}
