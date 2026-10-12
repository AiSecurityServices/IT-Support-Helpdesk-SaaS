@props(['title' => null])
<div {{ $attributes->merge(['class' => 'pf-card pf-card-pad']) }}>
  @if($title)<h2 class="pf-card-title">{{ $title }}</h2>@endif
  {{ $slot }}
</div>
