<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>@yield('title', config('branding.product_name')) — {{ config('branding.product_name') }}</title>
<link rel="canonical" href="{{ url()->current() }}">
@include('layouts.partials.head')
<script type="application/ld+json">{"@context":"https://schema.org","@type":"SoftwareApplication","name":"{{ config('branding.product_name') }}","applicationCategory":"BusinessApplication","operatingSystem":"Web","offers":{"@type":"Offer","price":"19","priceCurrency":"GBP"}}</script>
</head>
<body class="pf-body">
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-cyan-500 focus:px-4 focus:py-2 focus:text-slate-950">Skip to main content</a>
<header class="sticky top-0 z-40 border-b border-white/10 bg-[#04070f]/90 backdrop-blur">
  <div class="mx-auto flex max-w-7xl items-center gap-8 px-4 py-3.5 sm:px-6">
    @include('layouts.partials.wordmark')
    <nav class="hidden items-center gap-7 text-[0.9rem] font-medium text-slate-300 lg:flex" aria-label="Site">
      <a class="transition hover:text-cyan-300" href="{{ route('features') }}">Product</a>
      <a class="transition hover:text-cyan-300" href="{{ route('use-cases') }}">Solutions</a>
      <a class="transition hover:text-cyan-300" href="{{ route('resources') }}">Resources</a>
      <a class="transition hover:text-cyan-300" href="{{ route('security') }}">Security</a>
      <a class="transition hover:text-cyan-300" href="{{ route('pricing') }}">Pricing</a>
    </nav>
    <div class="ml-auto flex items-center gap-2.5 text-sm">
      <button onclick="toggleTheme()" class="rounded-lg border border-white/15 p-2 text-slate-300 transition hover:border-cyan-400/50 hover:text-cyan-300" aria-label="Toggle colour theme"><x-icon name="moon" /></button>
      @auth
        <a href="{{ route('dashboard') }}" class="pf-btn pf-btn-bright pf-btn-sm">Open dashboard <x-icon name="arrow" /></a>
      @else
        <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 font-medium text-slate-200 transition hover:text-white sm:block">Sign in</a>
        <a href="{{ route('register') }}" class="pf-btn pf-btn-bright pf-btn-sm">Start free trial</a>
      @endauth
      <button class="rounded-lg border border-white/15 p-2 text-slate-200 lg:hidden" aria-label="Open menu" aria-expanded="false" onclick="var m=document.getElementById('mobileMenu');var o=m.classList.toggle('hidden');this.setAttribute('aria-expanded',String(!o))"><x-icon name="menu" /></button>
    </div>
  </div>
  <div id="mobileMenu" class="hidden border-t border-white/10 px-4 py-3 lg:hidden">
    <nav class="grid gap-1 text-[0.95rem] font-medium text-slate-200" aria-label="Mobile">
      <a class="rounded-lg px-3 py-2.5 hover:bg-white/5" href="{{ route('features') }}">Product</a>
      <a class="rounded-lg px-3 py-2.5 hover:bg-white/5" href="{{ route('use-cases') }}">Solutions</a>
      <a class="rounded-lg px-3 py-2.5 hover:bg-white/5" href="{{ route('resources') }}">Resources</a>
      <a class="rounded-lg px-3 py-2.5 hover:bg-white/5" href="{{ route('security') }}">Security</a>
      <a class="rounded-lg px-3 py-2.5 hover:bg-white/5" href="{{ route('pricing') }}">Pricing</a>
      <a class="rounded-lg px-3 py-2.5 hover:bg-white/5" href="{{ route('login') }}">Sign in</a>
    </nav>
  </div>
</header>
<main id="main-content" tabindex="-1">@yield('content')</main>
<footer class="pf-footer bg-[#04070f] pb-10 pt-14 text-slate-300">
  <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-5">
    <div class="lg:col-span-2">
      @include('layouts.partials.wordmark')
      <p class="pf-measure mt-4 max-w-sm text-sm leading-relaxed text-slate-400">Precision digital operations for IT support teams. Tickets, SLAs, assets and technicians — connected in one engineered system.</p>
      <p class="mt-4 font-mono text-xs tracking-wider text-slate-500">{{ config('branding.company_name') }} · {{ config('branding.support_email') }}</p>
    </div>
    <div><h3>Product</h3><ul class="space-y-2.5"><li><a href="{{ route('features') }}">Features</a></li><li><a href="{{ route('pricing') }}">Pricing</a></li><li><a href="{{ route('security') }}">Security</a></li><li><a href="{{ route('resources') }}">Resources</a></li></ul></div>
    <div><h3>Solutions</h3><ul class="space-y-2.5"><li><a href="{{ route('use-cases') }}">Managed providers</a></li><li><a href="{{ route('use-cases') }}#internal-it">Internal IT</a></li><li><a href="{{ route('use-cases') }}#consultancies">Consultancies</a></li><li><a href="{{ route('use-cases') }}#smb">SMBs</a></li></ul></div>
    <div><h3>Legal</h3><ul class="space-y-2.5"><li><a href="{{ route('privacy') }}">Privacy</a></li><li><a href="{{ route('terms') }}">Terms</a></li><li><a href="{{ route('contact') }}">Contact</a></li><li><a href="{{ route('faq') }}">FAQ</a></li></ul></div>
  </div>
  <div class="mx-auto mt-12 flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 text-xs text-slate-500 sm:px-6">
    <span>© {{ date('Y') }} {{ config('branding.company_name') }}. All rights reserved.</span>
    <span class="font-mono tracking-widest">PRECISION DIGITAL OPERATIONS</span>
  </div>
</footer>
@stack('scripts')
</body>
</html>
