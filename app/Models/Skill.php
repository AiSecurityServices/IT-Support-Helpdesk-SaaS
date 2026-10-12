<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['tenant_id', 'name'];

    public function technicians()
    {
        return $this->belongsToMany(User::class, 'skill_user');
    }
}
