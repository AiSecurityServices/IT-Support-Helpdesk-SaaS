<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Paginator::useBootstrapFive();

        // Share branding + tenant context with every view.
        View::composer('*', function ($view) {
            $view->with('brand', config('branding'));
            try {
                $view->with('currentTenant', currentTenant());
            } catch (\Throwable $e) {
                $view->with('currentTenant', null);
            }
        });

        Blade::if('staff', fn () => auth()->check() && auth()->user()->isStaff());
        Blade::if('customer', fn () => auth()->check() && auth()->user()->isCustomer());
        Blade::if('superadmin', fn () => auth()->check() && auth()->user()->is_super_admin);
    }
}
