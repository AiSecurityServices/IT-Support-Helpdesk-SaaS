<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
<title>@yield('title', config('branding.product_name')) — {{ config('branding.product_name') }}</title>
@include('layouts.partials.head')
<style>:focus-visible{outline:2px solid #22d3ee;outline-offset:2px}</style>
</head>
<body class="pf-body h-full">
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-cyan-500 focus:px-4 focus:py-2 focus:text-slate-950">Skip to main content</a>
@if(session()->has('impersonate.original_id'))
<div class="bg-red-700 px-4 py-2 text-center text-sm font-medium text-white" role="alert">
  Support impersonation ACTIVE — acting as {{ auth()->user()->email }}. All actions are audited.
  <form method="POST" action="{{ route('impersonation.stop') }}" class="ml-3 inline">@csrf
    <button class="underline">Stop impersonation</button>
  </form>
</div>
@endif
<div class="min-h-full lg:flex">
  {{-- Operational sidebar --}}
  <aside class="pf-sidebar hidden w-[15.5rem] shrink-0 flex-col lg:flex" id="sidebar" aria-label="Workspace">
    <div class="px-5 pb-2 pt-5">
      <a href="{{ route('dashboard') }}" class="pf-wordmark !text-white" aria-label="Workspace home">
        <span class="pf-mark" aria-hidden="true"></span><span class="pf-wordmark-name !text-[1.02rem]">{{ config('branding.product_name') }}</span>
      </a>
    </div>
    @if(isset($currentTenant) && $currentTenant)
    <div class="mx-4 mb-3 rounded-[10px] border border-white/10 bg-white/[0.04] px-3 py-2.5">
      <div class="truncate text-[0.8rem] font-semibold text-white">{{ $currentTenant->name }}</div>
      <div class="mt-0.5 font-mono text-[0.64rem] uppercase tracking-[0.14em] text-cyan-300/80">{{ auth()->user()->role ?? 'member' }}</div>
    </div>
    @endif
    <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 pb-3" aria-label="Primary">
      @include('layouts.partials.nav')
    </nav>
    <div class="border-t border-white/10 p-4 font-mono text-[0.62rem] uppercase tracking-[0.16em] text-slate-500">
      @if(config('branding.show_powered_by')) Precision operations @endif
    </div>
  </aside>

  <div class="min-w-0 flex-1">
    <header class="pf-topbar sticky top-0 z-30 flex items-center gap-3 px-4 py-2.5 sm:px-6">
      <button class="rounded-lg border border-slate-300 p-2 lg:hidden dark:border-slate-600" onclick="document.getElementById('mobileNav').classList.toggle('hidden')" aria-label="Open menu"><x-icon name="menu" /></button>
      <form action="{{ route('search') }}" method="GET" class="hidden max-w-md flex-1 sm:block" role="search">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search tickets, customers, assets…" aria-label="Search"
          class="pf-input !py-2">
      </form>
      <div class="ml-auto flex items-center gap-2">
        <button onclick="toggleTheme()" class="rounded-lg border border-slate-300 p-2 dark:border-slate-600" aria-label="Toggle colour theme"><x-icon name="moon" /></button>
        <a href="{{ route('notifications.index') }}" class="relative rounded-lg border border-slate-300 p-2 dark:border-slate-600" aria-label="Notifications, {{ auth()->user()->unreadNotifications()->count() }} unread"><x-icon name="bell" />
          @if(auth()->user()->unreadNotifications()->count())
          <span class="absolute -right-1 -top-1 grid h-4 min-w-[1rem] place-items-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">{{ auth()->user()->unreadNotifications()->count() }}</span>
          @endif
        </a>
        <x-dropdown align="right" width="48">
          <x-slot name="trigger">
            <button class="flex items-center gap-2 rounded-lg border border-slate-300 py-1 pl-1 pr-2 text-sm font-medium dark:border-slate-600">
              <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="h-7 w-7 rounded-full"> <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
            </button>
          </x-slot>
          <x-slot name="content">
            <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
            @if(auth()->user()->is_super_admin)<x-dropdown-link :href="route('admin.dashboard')">Platform admin</x-dropdown-link>@endif
            <form method="POST" action="{{ route('logout') }}">@csrf
              <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();this.closest('form').submit();">Log out</x-dropdown-link>
            </form>
          </x-slot>
        </x-dropdown>
      </div>
    </header>
    <div id="mobileNav" class="pf-sidebar hidden border-b p-4 lg:hidden">
      <nav class="space-y-0.5" aria-label="Mobile">@include('layouts.partials.nav')</nav>
    </div>

    <main id="main-content" class="mx-auto max-w-7xl p-4 sm:p-6" tabindex="-1">
      @if(session('success'))<div class="pf-alert pf-alert-ok mb-4" role="alert">{{ session('success') }}</div>@endif
      @if(session('error'))<div class="pf-alert pf-alert-err mb-4" role="alert">{{ session('error') }}</div>@endif
      @if($errors->any())<div class="pf-alert pf-alert-err mb-4" role="alert"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
      @yield('breadcrumbs')
      <div class="pf-pagehead">
        <div>
          @hasSection('eyebrow')<div class="pf-eyebrow">@yield('eyebrow')</div>@endif
          <h1>@yield('heading')</h1>
        </div>
        <div class="ml-auto flex flex-wrap gap-2">@yield('actions')</div>
      </div>
      @yield('content')
    </main>
  </div>
</div>
@stack('scripts')
</body>
</html>
