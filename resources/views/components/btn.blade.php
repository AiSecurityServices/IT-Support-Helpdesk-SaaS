@props(['variant' => 'primary'])
@php $styles = ['primary'=>'pf-btn pf-btn-primary','secondary'=>'pf-btn pf-btn-ghost','danger'=>'pf-btn pf-btn-danger','ghost'=>'pf-btn pf-btn-quiet','bright'=>'pf-btn pf-btn-bright']; @endphp
<button {{ $attributes->merge(['class' => ($styles[$variant] ?? $styles['primary'])]) }}>{{ $slot }}</button>
