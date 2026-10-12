<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketApiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);
        $query = Ticket::where('tenant_id', currentTenantId())->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        return $query->paginate(25);
    }

    public function store(Request $request, TicketService $service)
    {
        $this->authorize('create', Ticket::class);
        $data = $request->validate([
            'subject' => 'required|string|max:255', 'description' => 'required|string',
            'customer_id' => 'nullable|exists:customers,id',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'category_id' => 'nullable|exists:categories,id',
        ]);
        $ticket = $service->create($data + ['source' => 'api'], $request->user());

        return response()->json($ticket, 201);
    }

    public function show(Request $request, Ticket $ticket)
    {
        abort_unless((int) $ticket->tenant_id === (int) currentTenantId(), 404);
        $this->authorize('view', $ticket);

        return $ticket->load(['messages' => fn ($q) => $q->when(
            $request->user()->isCustomer(), fn ($w) => $w->where('visibility', 'public')
        )]);
    }

    public function reply(Request $request, Ticket $ticket, TicketService $service)
    {
        abort_unless((int) $ticket->tenant_id === (int) currentTenantId(), 404);
        $this->authorize('reply', $ticket);
        $request->validate(['body' => 'required|string|max:20000']);
        $message = $service->addMessage($ticket, $request->user(), $request->input('body'), 'public');

        return response()->json($message, 201);
    }
}
