<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>@yield('title','Sign in') — {{ config('branding.product_name') }}</title>
@include('layouts.partials.head')
</head>
<body class="pf-body">
<div class="pf-dark-zone pf-grid-lines min-h-screen">
  <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-cyan-500 focus:px-4 focus:py-2 focus:text-slate-950">Skip to main content</a>
  <main id="main-content" tabindex="-1" class="mx-auto flex min-h-screen w-full max-w-md flex-col items-center justify-center px-4 py-10">
    <a href="{{ route('home') }}" class="pf-wordmark mb-8 !text-white" aria-label="Back to homepage">
      <span class="pf-mark" aria-hidden="true"></span><span class="pf-wordmark-name">{{ config('branding.product_name') }}</span>
    </a>
    <div class="dark pf-card w-full !border-white/10 !bg-white/[0.04] p-6 text-slate-100 backdrop-blur sm:p-8" style="box-shadow:var(--pf-shadow-2)">
      @if(session('status'))<div class="pf-alert pf-alert-ok mb-4" role="status">{{ session('status') }}</div>@endif
      @if($errors->any())<div class="pf-alert pf-alert-err mb-4" role="alert"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
      @yield('content')
    </div>
    <p class="mt-6 font-mono text-[0.66rem] uppercase tracking-[0.2em] text-slate-500">Precision digital operations</p>
  </main>
</div>
</body>
</html>
