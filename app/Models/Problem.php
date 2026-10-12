<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'title', 'description', 'status', 'root_cause',
        'workaround', 'known_error', 'owner_id',
    ];

    protected $casts = ['known_error' => 'boolean'];

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'problem_ticket');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
