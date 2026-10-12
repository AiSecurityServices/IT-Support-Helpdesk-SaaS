<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'imported_by', 'entity', 'original_filename',
        'rows_total', 'rows_imported', 'rows_skipped', 'errors',
    ];

    protected $casts = ['errors' => 'array'];
}
