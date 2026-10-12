@props(['label' => null, 'error' => null, 'hint' => null])
<div class="mb-3">
  <label class="mb-1 block text-sm">
    @if($label)<span class="pf-label">{{ $label }}</span>@endif
    {{ $slot }}
  </label>
  @if($hint)<p class="pf-hint">{{ $hint }}</p>@endif
  @if($error)<p class="pf-error" role="alert">{{ $error }}</p>@endif
</div>
