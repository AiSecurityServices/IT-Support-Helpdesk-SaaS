<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailEvent extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'recipient', 'kind', 'provider', 'payload'];

    protected $casts = ['payload' => 'array'];
}
