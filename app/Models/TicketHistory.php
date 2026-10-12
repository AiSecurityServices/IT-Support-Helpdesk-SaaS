<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketHistory extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['tenant_id', 'ticket_id', 'actor_id', 'action', 'changes'];

    protected $casts = ['changes' => 'array'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
