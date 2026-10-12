@props(['eyebrow' => '', 'lede' => ''])
<section class="pf-dark-zone pf-grid-lines">
  <div class="mx-auto max-w-7xl px-4 pb-14 pt-14 sm:px-6 lg:pb-20 lg:pt-20">
    <div class="max-w-3xl">
      @if($eyebrow)<p class="pf-eyebrow">{{ $eyebrow }}</p>@endif
      <h1 class="pf-display text-4xl font-bold text-white sm:text-5xl">{{ $slot }}</h1>
      @if($lede)<p class="mt-4 max-w-2xl text-[1.02rem] leading-relaxed text-slate-300">{{ $lede }}</p>@endif
    </div>
  </div>
</section>
