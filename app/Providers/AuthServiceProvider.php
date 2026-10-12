<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Ticket::class => \App\Policies\TicketPolicy::class,
        \App\Models\Customer::class => \App\Policies\TenantRecordPolicy::class,
        \App\Models\Contact::class => \App\Policies\TenantRecordPolicy::class,
        \App\Models\Asset::class => \App\Policies\TenantRecordPolicy::class,
        \App\Models\Invoice::class => \App\Policies\TenantRecordPolicy::class,
        \App\Models\Team::class => \App\Policies\TenantRecordPolicy::class,
        \App\Models\RemoteSession::class => \App\Policies\TenantRecordPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
