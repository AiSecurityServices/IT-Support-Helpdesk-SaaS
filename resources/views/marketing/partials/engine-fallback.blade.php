{{-- Static fallback: stylised instrument dial evoking the Service Engine. --}}
<svg viewBox="0 0 560 420" role="img" aria-label="Stylised illustration of the Precision Service Engine" style="width:100%;height:100%">
  <defs>
    <linearGradient id="pfFbCore" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#1a2547"/><stop offset=".55" stop-color="#0b1327"/><stop offset="1" stop-color="#101a33"/>
    </linearGradient>
    <linearGradient id="pfFbIce" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0" stop-color="#22d3ee"/><stop offset="1" stop-color="#6366f1"/>
    </linearGradient>
  </defs>
  <circle cx="280" cy="210" r="150" fill="none" stroke="#1e2c4f" stroke-width="1.5"/>
  <circle cx="280" cy="210" r="118" fill="none" stroke="#27436b" stroke-width="1" stroke-dasharray="3 7"/>
  <g stroke="#3b82a6" stroke-width="2">
    @for($i = 0; $i < 24; $i++)
      <line x1="280" y1="66" x2="280" y2="78" transform="rotate({{ $i * 15 }} 280 210)" opacity="{{ $i % 6 === 0 ? 1 : 0.45 }}"/>
    @endfor
  </g>
  <ellipse cx="280" cy="228" rx="104" ry="22" fill="url(#pfFbCore)" stroke="#3b587f" stroke-width="1.5"/>
  <ellipse cx="280" cy="196" rx="80" ry="17" fill="url(#pfFbCore)" stroke="#3b587f" stroke-width="1.5"/>
  <ellipse cx="280" cy="196" rx="80" ry="17" fill="none" stroke="#22d3ee" stroke-width="2" opacity="0.7"/>
  <rect x="262" y="120" width="36" height="66" rx="4" fill="#101a33" stroke="#4b6584" stroke-width="1.5"/>
  <polygon points="280,78 298,104 280,130 262,104" fill="#1a2547" stroke="#67e8f9" stroke-width="2"/>
  <circle cx="280" cy="104" r="7" fill="#22d3ee"/>
  <path d="M70 300 C 170 260, 200 150, 280 150 S 390 240, 490 200" fill="none" stroke="url(#pfFbIce)" stroke-width="3" stroke-linecap="round"/>
  <path d="M80 140 C 180 170, 220 260, 300 262 S 430 170, 486 236" fill="none" stroke="#6366f1" stroke-width="2" opacity="0.65" stroke-linecap="round"/>
  <g fill="#22d3ee">
    <circle cx="70" cy="300" r="5"/><circle cx="490" cy="200" r="5"/><circle cx="300" cy="262" r="4"/>
  </g>
  <g fill="#818cf8">
    <circle cx="80" cy="140" r="4"/><circle cx="486" cy="236" r="4"/>
  </g>
</svg>
