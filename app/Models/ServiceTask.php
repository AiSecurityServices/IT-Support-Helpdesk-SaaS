<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTask extends Model
{
    use HasFactory;

    protected $fillable = ['service_request_id', 'title', 'owner_id', 'status', 'evidence', 'completed_at'];

    protected $casts = ['completed_at' => 'datetime'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
