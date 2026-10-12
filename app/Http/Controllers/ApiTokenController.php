<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Scoped API credentials: named tokens with ability allow-lists, expiry,
 * revocation and last-used visibility. Owners may also manage token-only
 * service accounts for integrations.
 */
class ApiTokenController extends Controller
{
    public const ABILITIES = ['tickets:read', 'tickets:write', 'customers:read', 'assets:read', 'reports:read'];

    protected function tenantId(): int
    {
        return (int) currentTenantId();
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $tokens = $user->tokens()->orderByDesc('created_at')->get();
        $serviceAccounts = $user->hasRole('owner')
            ? User::where('tenant_id', $this->tenantId())->where('is_service_account', true)->orderBy('name')->get()
            : collect();

        return view('settings.tokens', compact('tokens', 'serviceAccounts'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'abilities.*' => 'in:'.implode(',', self::ABILITIES),
            'expires_at' => 'nullable|date|after:now|before:'.now()->addYears(2)->toDateString(),
        ]);

        $token = $user->createToken(
            $data['name'],
            $data['abilities'] ?? ['tickets:read'],
            $data['expires_at'] ?? null ? \Carbon\Carbon::parse($data['expires_at']) : null
        );
        audit('api.token_issued', null, ['name' => $data['name']]);

        return back()->with('plain_token', $token->plainTextToken)
            ->with('success', 'Token created — copy it now, it will never be shown again.');
    }

    public function revoke(Request $request, string $tokenId)
    {
        $token = $request->user()->tokens()->findOrFail($tokenId);
        $token->delete();
        audit('api.token_revoked', null, ['name' => $token->name]);

        return back()->with('success', 'Token revoked. It stops working immediately.');
    }

    /** Owner-only: create a token-only service account for integrations. */
    public function storeServiceAccount(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);
        $data = $request->validate(['name' => 'required|string|max:255']);

        $account = User::create([
            'name' => '[service] '.$data['name'],
            'email' => 'svc-'.Str::random(8).'@'.$this->tenantId().'.local',
            'password' => Hash::make(Str::random(48)),
            'tenant_id' => $this->tenantId(), 'role' => 'technician',
            'is_service_account' => true, 'is_active' => true,
            'email_verified_at' => now(),
        ]);
        currentTenant()->users()->attach($account->id, ['role' => 'technician', 'joined_at' => now()]);
        audit('api.service_account_created', $account);

        return back()->with('success', 'Service account created. Issue it a token below (sign in as it is disabled).');
    }
}
