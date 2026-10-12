<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecoveryCode extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'code_hash', 'used_at'];

    protected $casts = ['used_at' => 'datetime'];
}
