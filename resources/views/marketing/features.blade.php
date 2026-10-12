@extends('layouts.marketing')
@section('title', 'Product tour: ticketing, SLAs, assets and automation')
@section('content')
@include('marketing.partials.pagehead', ['eyebrow' => 'Product', 'slot' => 'One platform, every discipline of support.', 'lede' => 'Ticketing, service levels, customers, assets, technicians, automation and billing — designed as a single instrument, not seven tabs.'])
<section class="pf-section bg-white dark:bg-slate-950" id="tickets">
  <div class="mx-auto max-w-7xl px-4 sm:px-6">
    <div class="pf-section-head pf-reveal"><p class="pf-eyebrow">Ticketing</p><h2>Queues that think like a service desk.</h2><p>Priorities, assignments, merges, watchers, saved replies, collision-free editing and a complete audit trail on every ticket.</p></div>
    <div class="pf-mini-ui pf-reveal" role="img" aria-label="Illustrated ticket queue"><div class="pf-mini-ui-bar"><i></i><i></i><i></i><span class="ml-2 font-mono text-[0.66rem] tracking-widest text-slate-400">QUEUE · SAMPLE</span></div><div class="pf-mini-row"><x-badge color="red">Urgent</x-badge><span>#2844 Exchange DAG unhealthy · Acme Ltd</span></div><div class="pf-mini-row"><x-badge color="yellow">High</x-badge><span>#2841 VPN drops afternoons · Globex</span></div><div class="pf-mini-row"><x-badge color="blue">Normal</x-badge><span>#2839 New starter laptop · Initech</span></div></div>
  </div>
</section>
<section class="pf-section pf-dark-zone" id="sla">
  <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2">
    <div class="pf-reveal"><p class="pf-eyebrow">Service levels</p><h2 class="pf-display text-3xl font-bold text-white">SLAs measured in business hours, not wishful thinking.</h2><p class="mt-3 leading-relaxed text-slate-300">Response and resolution targets per priority. Calendars with weekends and holidays. Clocks that pause while you wait on the customer — and escalations that fire themselves when targets slip.</p></div>
    <div class="pf-mini-ui pf-reveal" role="img" aria-label="Illustrated SLA timers"><div class="pf-mini-ui-bar"><i></i><i></i><i></i><span class="ml-2 font-mono text-[0.66rem] tracking-widest text-slate-400">SLA · SAMPLE</span></div><div class="pf-mini-row"><x-badge color="green">On track</x-badge><span>First response · 62% of 1h elapsed</span></div><div class="pf-mini-row"><x-badge color="yellow">At risk</x-badge><span>Resolution · 84% of 8h elapsed</span></div><div class="pf-mini-row"><x-badge color="red">Breached</x-badge><span>Escalated to L2 · manager paged</span></div></div>
  </div>
</section>
<section class="pf-section bg-white dark:bg-slate-950" id="operations">
  <div class="mx-auto max-w-7xl px-4 sm:px-6">
    <div class="pf-section-head pf-reveal"><p class="pf-eyebrow">Operations</p><h2>Customers, assets, technicians — one record of truth.</h2></div>
    <div class="grid gap-5 md:grid-cols-3">
      @foreach([['users','Customer organisations','Contacts, sites, portals and full service history per client.'],['device','Asset inventory','Every device, warranty and assignment — linked to the tickets that touched it.'],['wrench','Technicians & teams','Skills, workload, and queues routed to the right hands first time.']] as $f)
      <div class="pf-card pf-card-flat pf-card-pad pf-reveal"><span class="text-cyan-700 dark:text-cyan-300"><x-icon name="{{ $f[0] }}" class="pf-icon-lg" /></span><h3 class="pf-display mt-3 font-semibold">{{ $f[1] }}</h3><p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $f[2] }}</p></div>
      @endforeach
    </div>
    <div class="pf-reveal mt-8 grid gap-5 md:grid-cols-2">
      <div class="pf-card pf-card-flat pf-card-pad"><h3 class="pf-display font-semibold">Automation that earns trust</h3><p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">Trigger-based rules with dry-run previews and a full execution log. Nothing fires blind.</p></div>
      <div class="pf-card pf-card-flat pf-card-pad"><h3 class="pf-display font-semibold">Billing without the spreadsheet</h3><p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">Billable time flows into invoices with tax, partial payments and PDFs — ready for finance.</p></div>
    </div>
    <p class="mt-10 text-center"><a href="{{ route('pricing') }}" class="pf-btn pf-btn-primary pf-btn-lg">See plans and start trial</a></p>
  </div>
</section>
@endsection
