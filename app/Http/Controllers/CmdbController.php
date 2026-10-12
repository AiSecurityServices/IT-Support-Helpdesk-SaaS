<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CiRelationship;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Practical CMDB: typed relationships between CIs + impact view showing
 * related tickets/services. Every edge is tenant-scoped and audited.
 */
class CmdbController extends Controller
{
    protected function tenantId(): int
    {
        return (int) currentTenantId();
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);
        $rels = CiRelationship::where('tenant_id', $this->tenantId())->latest()->paginate(25);

        return view('cmdb.index', [
            'relationships' => $rels,
            'assets' => Asset::where('tenant_id', $this->tenantId())->orderBy('name')->limit(200)->get(),
            'customers' => Customer::where('tenant_id', $this->tenantId())->orderBy('company_name')->get(),
            'locations' => Location::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager', 'technician'), 403);
        $data = $request->validate([
            'from_type' => 'required|in:asset,customer,location,user',
            'from_id' => 'required|integer|min:1',
            'to_type' => 'required|in:asset,customer,location,user',
            'to_id' => 'required|integer|min:1',
            'relation' => 'required|in:depends_on,hosted_on,used_by,located_at,manages,member_of',
        ]);
        abort_if($data['from_type'] === $data['to_type'] && (int) $data['from_id'] === (int) $data['to_id'], 422, 'A CI cannot relate to itself.');

        // Both ends must exist inside this tenant (no cross-tenant edges).
        foreach ([['from_type', 'from_id'], ['to_type', 'to_id']] as [$t, $i]) {
            abort_unless($this->belongsToTenant($data[$t], (int) $data[$i]), 422, 'Unknown configuration item.');
        }

        $rel = CiRelationship::firstOrCreate(
            ['tenant_id' => $this->tenantId()] + $data,
            ['created_by' => $request->user()->id]
        );
        audit('cmdb.related', $rel, $data);

        return back()->with('success', 'Relationship recorded.');
    }

    public function destroy(Request $request, CiRelationship $relationship)
    {
        abort_unless((int) $relationship->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager'), 403);
        $relationship->delete();
        audit('cmdb.unrelated', $relationship);

        return back()->with('success', 'Relationship removed.');
    }

    /** Impact view: for a CI, show relationships + open tickets touching it. */
    public function impact(Request $request, string $type, int $id)
    {
        abort_unless($request->user()->isStaff(), 403);
        abort_unless(in_array($type, CiRelationship::TYPES, true), 404);
        abort_unless($this->belongsToTenant($type, $id), 404);

        $outgoing = CiRelationship::where('tenant_id', $this->tenantId())
            ->where('from_type', $type)->where('from_id', $id)->get();
        $incoming = CiRelationship::where('tenant_id', $this->tenantId())
            ->where('to_type', $type)->where('to_id', $id)->get();

        // Related open tickets: directly attached + via related assets/customers.
        $assetIds = $this->collectIds($type, $id, $outgoing, $incoming, 'asset');
        $customerIds = $this->collectIds($type, $id, $outgoing, $incoming, 'customer');
        if ($type === 'asset') {
            $assetIds[] = $id;
        }
        if ($type === 'customer') {
            $customerIds[] = $id;
        }

        $tickets = Ticket::with(['customer', 'assignee'])
            ->where('tenant_id', $this->tenantId())
            ->whereIn('status', Ticket::OPEN_STATUSES)
            ->where(fn ($q) => $q->whereIn('asset_id', $assetIds ?: [0])->orWhereIn('customer_id', $customerIds ?: [0]))
            ->orderBy('priority')->limit(50)->get();

        return view('cmdb.impact', [
            'type' => $type, 'id' => $id,
            'label' => CiRelationship::label($type, $id),
            'outgoing' => $outgoing, 'incoming' => $incoming, 'tickets' => $tickets,
        ]);
    }

    protected function collectIds(string $type, int $id, $outgoing, $incoming, string $want): array
    {
        $ids = [];
        foreach ([$outgoing, $incoming] as $set) {
            foreach ($set as $rel) {
                if ($rel->from_type === $want && ! ($rel->from_type === $type && (int) $rel->from_id === $id)) {
                    $ids[] = (int) $rel->from_id;
                }
                if ($rel->to_type === $want && ! ($rel->to_type === $type && (int) $rel->to_id === $id)) {
                    $ids[] = (int) $rel->to_id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    protected function belongsToTenant(string $type, int $id): bool
    {
        $tenantId = $this->tenantId();

        return match ($type) {
            'asset' => Asset::where('tenant_id', $tenantId)->where('id', $id)->exists(),
            'customer' => Customer::where('tenant_id', $tenantId)->where('id', $id)->exists(),
            'location' => Location::where('tenant_id', $tenantId)->where('id', $id)->exists(),
            'user' => User::where('tenant_id', $tenantId)->where('id', $id)->exists(),
            default => false,
        };
    }
}
