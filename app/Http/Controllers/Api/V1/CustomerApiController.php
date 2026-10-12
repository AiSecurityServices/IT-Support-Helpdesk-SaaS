<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerApiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        return Customer::where('tenant_id', currentTenantId())->latest()->paginate(25);
    }

    public function show(Request $request, Customer $customer)
    {
        abort_unless((int) $customer->tenant_id === (int) currentTenantId(), 404);
        $this->authorize('view', $customer);

        return $customer->load('contacts');
    }
}
