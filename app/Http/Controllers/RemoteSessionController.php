<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\RemoteSession;
use App\Models\Ticket;
use Illuminate\Http\Request;

class RemoteSessionController extends Controller
{
    public function index()
    {
        return view('remote.index', [
            'sessions' => RemoteSession::with(['ticket', 'asset', 'technician'])
                ->where('tenant_id', $this->tenantId())->latest()->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ticket_id' => 'nullable|exists:tickets,id', 'asset_id' => 'nullable|exists:assets,id',
            'provider' => 'required|in:teamviewer,anydesk,rdp,teams,zoom,other',
            'join_url' => 'nullable|url|max:1000', 'purpose' => 'nullable|string|max:1000',
            'customer_instructions' => 'nullable|string|max:2000',
            'expires_at' => 'nullable|date|after:now',
        ]);
        foreach (['ticket_id' => Ticket::class, 'asset_id' => Asset::class] as $field => $model) {
            if (! empty($data[$field])) {
                abort_unless($model::where('tenant_id', $this->tenantId())->where('id', $data[$field])->exists(), 422, 'Invalid reference.');
            }
        }
        $session = RemoteSession::create($data + [
            'tenant_id' => $this->tenantId(), 'technician_id' => $request->user()->id,
            'status' => 'scheduled',
        ]);
        audit('remote.created', $session);

        return back()->with('success', 'Remote session link saved. Share it with the customer only through the ticket.');
    }

    public function consent(Request $request, RemoteSession $session)
    {
        abort_unless((int) $session->tenant_id === $this->tenantId(), 404);
        $request->validate(['consent_confirmed' => 'required|accepted']);
        $session->consent_confirmed = true;
        $session->status = 'active';
        $session->save();
        audit('remote.consent', $session);

        return back()->with('success', 'Customer consent recorded — session marked active.');
    }

    public function complete(RemoteSession $session)
    {
        abort_unless((int) $session->tenant_id === $this->tenantId(), 404);
        $session->status = 'completed';
        $session->completed_at = now();
        $session->save();

        return back()->with('success', 'Session marked complete.');
    }
}
