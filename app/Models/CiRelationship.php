<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CiRelationship extends Model
{
    use BelongsToTenant, HasFactory;

    public const TYPES = ['asset', 'customer', 'location', 'user', 'service'];

    public const RELATIONS = ['depends_on', 'hosted_on', 'used_by', 'located_at', 'manages', 'member_of'];

    protected $fillable = [
        'tenant_id', 'from_type', 'from_id', 'to_type', 'to_id', 'relation', 'created_by',
    ];

    /** Resolve a CI reference to its model for display (tenant-guarded by caller). */
    public static function resolve(string $type, int $id)
    {
        return match ($type) {
            'asset' => Asset::withoutTenantScope()->find($id),
            'customer' => Customer::withoutTenantScope()->find($id),
            'location' => Location::withoutTenantScope()->find($id),
            'user' => User::find($id),
            default => null,
        };
    }

    public static function label(string $type, int $id): string
    {
        $m = static::resolve($type, $id);
        if (! $m) {
            return ucfirst($type).' #'.$id.' (deleted)';
        }

        return match ($type) {
            'asset' => $m->name.' ('.$m->asset_tag.')',
            'customer' => $m->company_name,
            'location' => $m->name,
            'user' => $m->name,
            default => ucfirst($type).' #'.$id,
        };
    }
}
