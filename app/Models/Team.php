<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['tenant_id', 'department_id', 'name', 'description', 'lead_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class);
    }

    public function lead()
    {
        return $this->belongsTo(User::class, 'lead_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
