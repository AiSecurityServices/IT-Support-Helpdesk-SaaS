{{-- Shared document head: fonts, Tailwind, Precision design system, theme, Alpine. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="@yield('meta_description', 'SupportFlow Cloud — precision digital operations for IT support teams.')">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/precision.css') }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { darkMode: 'class', theme: { extend: {
  colors: { brand: {50:'#eff6ff',100:'#dbeafe',500:'#0e7490',600:'#0e7490',700:'#155e75'} },
  fontFamily: { display: ['"Space Grotesk"','Inter','system-ui','sans-serif'], mono: ['"IBM Plex Mono"','monospace'] }
}}};
(function(){try{var t=localStorage.getItem('sf-theme');if(t==='dark'||(!t&&matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}}catch(e){}})();
function toggleTheme(){var d=document.documentElement.classList.toggle('dark');try{localStorage.setItem('sf-theme',d?'dark':'light')}catch(e){}}
</script>
<script defer src="https://unpkg.com/alpinejs@3.13.3/dist/cdn.min.js"></script>
@stack('head')
