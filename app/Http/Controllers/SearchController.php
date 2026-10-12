<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);
        $term = '%'.$request->input('q').'%';
        $tenantId = $this->tenantId();
        $user = $request->user();

        $tickets = Ticket::where('tenant_id', $tenantId)
            ->where(fn ($q) => $q->where('subject', 'like', $term)->orWhere('number', 'like', $term)->orWhere('description', 'like', $term))
            ->when($user->isCustomer(), fn ($q) => $q->where('customer_id', $user->customer_id))
            ->limit(10)->get();
        $customers = $user->isStaff()
            ? Customer::where('tenant_id', $tenantId)->where('company_name', 'like', $term)->limit(8)->get()
            : collect();
        $assets = \App\Models\Asset::where('tenant_id', $tenantId)
            ->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('asset_tag', 'like', $term)->orWhere('serial_number', 'like', $term))
            ->when($user->isCustomer(), fn ($q) => $q->where('customer_id', $user->customer_id))
            ->limit(8)->get();

        if ($request->wantsJson()) {
            return response()->json([
                'tickets' => $tickets->map(fn ($t) => ['number' => $t->number, 'subject' => $t->subject, 'url' => route('tickets.show', $t)]),
                'customers' => $customers->map(fn ($c) => ['name' => $c->company_name, 'url' => route('customers.show', $c)]),
                'assets' => $assets->map(fn ($a) => ['name' => $a->name, 'tag' => $a->asset_tag, 'url' => route('assets.show', $a)]),
            ]);
        }

        return view('search', compact('tickets', 'customers', 'assets') + ['q' => $request->input('q')]);
    }
}
