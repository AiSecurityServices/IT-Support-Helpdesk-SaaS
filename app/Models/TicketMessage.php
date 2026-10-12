<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['tenant_id', 'ticket_id', 'author_id', 'visibility', 'body'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isInternal(): bool
    {
        return $this->visibility === 'internal';
    }
}
