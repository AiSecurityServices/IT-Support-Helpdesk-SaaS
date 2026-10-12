<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MajorIncident extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['tenant_id', 'title', 'commander_id', 'status', 'summary'];

    public function commander()
    {
        return $this->belongsTo(User::class, 'commander_id');
    }

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'major_incident_ticket');
    }

    public function updates()
    {
        return $this->hasMany(MajorIncidentUpdate::class)->orderByDesc('created_at');
    }
}
