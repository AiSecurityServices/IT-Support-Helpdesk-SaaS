<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\SlaPolicy;
use Illuminate\Http\Request;

class SlaController extends Controller
{
    public function index()
    {
        abort_unless(request()->user()->canManage('configuration'), 403);

        return view('sla.index', [
            'policies' => SlaPolicy::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
            'holidays' => Holiday::where('tenant_id', $this->tenantId())->orderBy('date')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->canManage('configuration'), 403);
        $request->merge(['working_days' => $this->parseDays($request->input('working_days'))]);
        $data = $request->validate([
            'name' => 'required|string|max:255', 'description' => 'nullable|string|max:1000',
            'targets' => 'required|array',
            'targets.*.first_response' => 'required|integer|min:5|max:43200',
            'targets.*.resolution' => 'required|integer|min:15|max:86400',
            'working_days' => 'required|array|min:1', 'working_days.*' => 'integer|min:1|max:7',
            'work_start' => 'required|date_format:H:i', 'work_end' => 'required|date_format:H:i|after:work_start',
            'pause_on_pending_customer' => 'nullable|boolean', 'is_default' => 'nullable|boolean',
        ]);

        if (! empty($data['is_default'])) {
            SlaPolicy::where('tenant_id', $this->tenantId())->update(['is_default' => false]);
        }

        SlaPolicy::create([
            'tenant_id' => $this->tenantId(), 'name' => $data['name'], 'description' => $data['description'] ?? null,
            'targets' => $data['targets'],
            'working_hours' => ['days' => $data['working_days'], 'start' => $data['work_start'],
                'end' => $data['work_end'], 'timezone' => currentTenant()->timezone],
            'pause_on_pending_customer' => $request->boolean('pause_on_pending_customer', true),
            'is_default' => $request->boolean('is_default'), 'is_active' => true,
        ]);
        audit('sla.created');

        return back()->with('success', 'SLA policy created.');
    }

    public function update(Request $request, SlaPolicy $policy)
    {
        abort_unless((int) $policy->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->canManage('configuration'), 403);
        $request->merge(['working_days' => $this->parseDays($request->input('working_days'))]);
        $data = $request->validate([
            'name' => 'required|string|max:255', 'description' => 'nullable|string|max:1000',
            'targets' => 'required|array',
            'working_days' => 'required|array|min:1',
            'work_start' => 'required|date_format:H:i', 'work_end' => 'required|date_format:H:i|after:work_start',
            'pause_on_pending_customer' => 'nullable|boolean', 'is_active' => 'nullable|boolean',
        ]);
        if ($request->boolean('is_default')) {
            SlaPolicy::where('tenant_id', $this->tenantId())->where('id', '!=', $policy->id)->update(['is_default' => false]);
            $policy->is_default = true;
        }
        $policy->fill([
            'name' => $data['name'], 'description' => $data['description'] ?? null,
            'targets' => $data['targets'],
            'working_hours' => ['days' => $data['working_days'], 'start' => $data['work_start'],
                'end' => $data['work_end'], 'timezone' => currentTenant()->timezone],
            'pause_on_pending_customer' => $request->boolean('pause_on_pending_customer', true),
            'is_active' => $request->boolean('is_active', true),
        ])->save();

        audit('sla.updated', $policy);

        return back()->with('success', 'SLA policy updated. Deadlines are recalculated on next evaluation.');
    }

    public function storeHoliday(Request $request)
    {
        abort_unless($request->user()->canManage('configuration'), 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'date' => 'required|date', 'recurring' => 'nullable|boolean']);
        Holiday::updateOrCreate(
            ['tenant_id' => $this->tenantId(), 'date' => $data['date']],
            ['name' => $data['name'], 'recurring' => $request->boolean('recurring')]
        );

        return back()->with('success', 'Holiday saved.');
    }

    public function destroyHoliday(Holiday $holiday)
    {
        abort_unless((int) $holiday->tenant_id === $this->tenantId(), 404);
        abort_unless(request()->user()->canManage('configuration'), 403);
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }

    /** Accepts [1,2,3] or "1,2,3". */
    protected function parseDays($value): array
    {
        $parts = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_unique(array_filter(array_map(
            fn ($v) => is_numeric(trim((string) $v)) ? (int) trim((string) $v) : null, $parts
        ))));
    }
}
