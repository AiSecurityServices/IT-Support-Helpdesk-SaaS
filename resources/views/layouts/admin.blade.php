<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>@yield('title', 'Platform administration') — {{ config('branding.product_name') }}</title>
@include('layouts.partials.head')
</head>
<body class="pf-body">
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-cyan-500 focus:px-4 focus:py-2 focus:text-slate-950">Skip to main content</a>
<div class="flex min-h-screen">
<aside class="pf-sidebar hidden w-60 shrink-0 flex-col p-4 md:flex" aria-label="Platform">
  <div class="mb-1 flex items-center gap-2 px-1 pb-4">
    <span class="pf-mark" aria-hidden="true"></span>
    <div><div class="pf-display text-[0.95rem] font-bold text-white">Platform</div><div class="pf-mono text-[0.6rem] uppercase tracking-[0.2em] text-cyan-300/70">super admin</div></div>
  </div>
  <nav class="flex-1 space-y-0.5 text-sm">
    <a class="pf-navlink {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}"><x-icon name="dashboard" /> Overview</a>
    <a class="pf-navlink {{ request()->routeIs('admin.tenants*') ? 'is-active' : '' }}" href="{{ route('admin.tenants') }}"><x-icon name="users" /> Tenants</a>
    <a class="pf-navlink {{ request()->routeIs('admin.plans*') ? 'is-active' : '' }}" href="{{ route('admin.plans') }}"><x-icon name="card" /> Plans</a>
    <a class="pf-navlink {{ request()->routeIs('admin.audit') ? 'is-active' : '' }}" href="{{ route('admin.audit') }}"><x-icon name="file" /> Audit log</a>
    <a class="pf-navlink {{ request()->routeIs('admin.health') ? 'is-active' : '' }}" href="{{ route('admin.health') }}"><x-icon name="signal" /> System health</a>
    <a class="pf-navlink {{ request()->routeIs('admin.impersonation') ? 'is-active' : '' }}" href="{{ route('admin.impersonation') }}"><x-icon name="eye" /> Impersonation</a>
    <a class="pf-navlink" href="{{ route('dashboard') }}"><x-icon name="arrow" /> Back to workspace</a>
  </nav>
</aside>
<div class="min-w-0 flex-1 p-4 sm:p-6">
  @if(session('success'))<div class="pf-alert pf-alert-ok mb-4" role="alert">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="pf-alert pf-alert-err mb-4" role="alert"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  <div class="pf-pagehead"><div><h1>@yield('heading')</h1></div></div>
  @yield('content')
</div>
</div>
</body>
</html>
