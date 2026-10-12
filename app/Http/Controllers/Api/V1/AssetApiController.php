<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;

class AssetApiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Asset::class);

        return Asset::where('tenant_id', currentTenantId())->latest()->paginate(25);
    }

    public function show(Request $request, Asset $asset)
    {
        abort_unless((int) $asset->tenant_id === (int) currentTenantId(), 404);
        $this->authorize('view', $asset);

        return $asset;
    }
}
