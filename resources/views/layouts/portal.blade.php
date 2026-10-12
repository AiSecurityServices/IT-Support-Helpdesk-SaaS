<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>@yield('title', 'Customer portal') — {{ $currentTenant->name ?? config('branding.product_name') }}</title>
@include('layouts.partials.head')
</head>
<body class="pf-body">
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-cyan-500 focus:px-4 focus:py-2 focus:text-slate-950">Skip to main content</a>
<header class="pf-topbar sticky top-0 z-30">
  <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3 sm:px-6">
    <a href="{{ route('portal.dashboard') }}" class="pf-wordmark" aria-label="Portal home">
      <span class="pf-mark" aria-hidden="true"></span>
      <span><span class="pf-wordmark-name block !text-[1rem] leading-none">{{ $currentTenant->name ?? config('branding.product_name') }}</span>
      <span class="pf-mono text-[0.62rem] uppercase tracking-[0.18em] text-slate-500">support portal</span></span>
    </a>
    <nav class="ml-auto hidden items-center gap-1 text-sm font-medium md:flex" aria-label="Portal">
      <a class="rounded-lg px-3 py-2 hover:bg-slate-500/10" href="{{ route('portal.dashboard') }}">Home</a>
      <a class="rounded-lg px-3 py-2 hover:bg-slate-500/10" href="{{ route('portal.tickets') }}">My requests</a>
      <a class="rounded-lg px-3 py-2 hover:bg-slate-500/10" href="{{ route('portal.services') }}">Services</a>
      <a class="rounded-lg px-3 py-2 hover:bg-slate-500/10" href="{{ route('portal.kb') }}">Help articles</a>
      <a class="rounded-lg px-3 py-2 hover:bg-slate-500/10" href="{{ route('portal.assets') }}">My devices</a>
      <a class="rounded-lg px-3 py-2 hover:bg-slate-500/10" href="{{ route('portal.invoices') }}">Invoices</a>
      <a class="rounded-lg px-3 py-2 hover:bg-slate-500/10" href="{{ route('portal.profile') }}">Profile</a>
    </nav>
    <div class="ml-auto flex items-center gap-2 md:ml-2">
      <button onclick="toggleTheme()" class="rounded-lg border border-slate-300 p-2 dark:border-slate-600" aria-label="Toggle colour theme"><x-icon name="moon" /></button>
      <form method="POST" action="{{ route('logout') }}" class="inline">@csrf
        <button class="pf-btn pf-btn-quiet pf-btn-sm">Log out</button>
      </form>
      <button class="rounded-lg border border-slate-300 p-2 md:hidden dark:border-slate-600" aria-label="Open menu" onclick="document.getElementById('portalNav').classList.toggle('hidden')"><x-icon name="menu" /></button>
    </div>
  </div>
  <div id="portalNav" class="hidden border-t border-slate-200 px-4 py-3 md:hidden dark:border-slate-700">
    <nav class="grid gap-1 text-sm font-medium" aria-label="Portal mobile">
      <a class="rounded-lg px-3 py-2.5 hover:bg-slate-500/10" href="{{ route('portal.dashboard') }}">Home</a>
      <a class="rounded-lg px-3 py-2.5 hover:bg-slate-500/10" href="{{ route('portal.tickets') }}">My requests</a>
      <a class="rounded-lg px-3 py-2.5 hover:bg-slate-500/10" href="{{ route('portal.services') }}">Services</a>
      <a class="rounded-lg px-3 py-2.5 hover:bg-slate-500/10" href="{{ route('portal.kb') }}">Help articles</a>
      <a class="rounded-lg px-3 py-2.5 hover:bg-slate-500/10" href="{{ route('portal.assets') }}">My devices</a>
      <a class="rounded-lg px-3 py-2.5 hover:bg-slate-500/10" href="{{ route('portal.invoices') }}">Invoices</a>
      <a class="rounded-lg px-3 py-2.5 hover:bg-slate-500/10" href="{{ route('portal.profile') }}">Profile</a>
    </nav>
  </div>
</header>
<main id="main-content" class="mx-auto max-w-6xl p-4 sm:p-6" tabindex="-1">
  @if(session('success'))<div class="pf-alert pf-alert-ok mb-4" role="alert">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="pf-alert pf-alert-err mb-4" role="alert"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  <div class="pf-pagehead"><div><h1>@yield('heading')</h1></div><div class="ml-auto flex flex-wrap gap-2">@yield('actions')</div></div>
  @yield('content')
</main>
</body>
</html>
