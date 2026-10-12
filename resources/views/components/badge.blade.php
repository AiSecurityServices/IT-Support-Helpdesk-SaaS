@props(['color' => 'slate'])
@php
$map = ['green'=>'is-ok','red'=>'is-bad','yellow'=>'is-warn','blue'=>'is-info','slate'=>'','purple'=>'is-violet'];
$tone = $map[$color] ?? '';
@endphp
<span {{ $attributes->merge(['class' => 'pf-badge '.$tone]) }}><span class="pf-dot" aria-hidden="true"></span>{{ $slot }}</span>
