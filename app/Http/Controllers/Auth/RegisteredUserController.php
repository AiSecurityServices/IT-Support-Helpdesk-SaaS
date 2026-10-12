<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function create()
    {
        $invitation = null;
        if (request('invitation')) {
            $invitation = \App\Models\TenantInvitation::where('token', request('invitation'))->first();
            abort_unless($invitation?->isValid(), 410, 'This invitation has expired or was already used.');
        }

        return view('auth.register', compact('invitation'));
    }

    /**
     * Tenant-aware registration: creates a new workspace + trial
     * subscription + owner account, or accepts a staff invitation.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::min(10)->mixedCase()->numbers()],
            'company' => ['required_without:invitation', 'nullable', 'string', 'max:255'],
            'invitation' => ['nullable', 'string'],
        ]);

        // Invitation acceptance path.
        if ($request->invitation) {
            return $this->acceptInvitation($request);
        }

        return DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name' => $request->company,
                'email' => $request->email,
                'status' => 'trial',
                'timezone' => config('branding.default_timezone'),
                'currency' => config('branding.default_currency'),
                'trial_ends_at' => now()->addDays(config('branding.trial_days', 14)),
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tenant_id' => $tenant->id,
                'role' => 'owner',
            ]);
            $tenant->users()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);

            $plan = Plan::where('slug', 'starter')->where('is_active', true)->first();
            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan?->id,
                'status' => 'trial',
                'interval' => 'monthly',
                'trial_ends_at' => $tenant->trial_ends_at,
                'current_period_start' => now(),
                'current_period_end' => $tenant->trial_ends_at,
                'provider' => 'manual',
            ]);

            // Seed workspace defaults (categories + default SLA policy).
            app(\App\Services\ProvisioningService::class)->seedDefaults($tenant);

            event(new Registered($user));
            Auth::login($user);
            audit('tenant.registered', $tenant, ['plan' => $plan?->slug]);

            return redirect(RouteServiceProvider::HOME)->with('success', 'Welcome! Your 14-day free trial has started.');
        });
    }

    protected function acceptInvitation(Request $request)
    {
        $invitation = \App\Models\TenantInvitation::where('token', $request->invitation)->first();
        abort_unless($invitation?->isValid(), 410, 'This invitation has expired or was already used.');
        abort_unless(strcasecmp($invitation->email, $request->email) === 0, 422, 'This invitation was sent to a different email address.');

        // Enforce plan seat limits.
        $seats = $invitation->tenant->activeSubscription?->plan?->technician_seats;
        if ($seats && $invitation->tenant->users()->count() >= $seats) {
            return back()->withErrors(['email' => 'This workspace has reached its technician seat limit. Ask the owner to upgrade.']);
        }

        return DB::transaction(function () use ($request, $invitation) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tenant_id' => $invitation->tenant_id,
                'role' => $invitation->role,
            ]);
            $invitation->tenant->users()->attach($user->id, ['role' => $invitation->role, 'joined_at' => now()]);
            $invitation->accepted_at = now();
            $invitation->save();

            event(new Registered($user));
            Auth::login($user);

            return redirect(RouteServiceProvider::HOME)->with('success', 'You joined '.$invitation->tenant->name.'.');
        });
    }
}
