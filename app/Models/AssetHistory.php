<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetHistory extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['tenant_id', 'asset_id', 'actor_id', 'action', 'details'];

    protected $table = 'asset_histories';
}
