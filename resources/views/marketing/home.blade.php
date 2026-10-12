@extends('layouts.marketing')
@section('title', 'One command centre for exceptional IT support')
@section('meta_description', 'SupportFlow Cloud connects customer requests, technicians, SLAs and IT assets in one precision-engineered support platform.')

@section('content')
{{-- ============ 1 · Editorial 3D hero ============ --}}
<section class="pf-dark-zone pf-grid-lines relative overflow-hidden">
  <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 pb-16 pt-14 sm:px-6 lg:grid-cols-2 lg:gap-6 lg:pb-24 lg:pt-20">
    <div class="pf-reveal is-in max-w-xl">
      <p class="pf-eyebrow">Precision digital operations</p>
      <h1 class="pf-display text-[2.6rem] font-bold leading-[1.04] text-white sm:text-6xl">One command centre for exceptional IT support.</h1>
      <p class="mt-5 max-w-lg text-[1.05rem] leading-relaxed text-slate-300">Manage customer requests, service levels, IT assets and technician workflows in one connected support platform.</p>
      <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ route('register') }}" class="pf-btn pf-btn-bright pf-btn-lg">Start free trial <x-icon name="arrow" /></a>
        <a href="{{ route('features') }}" class="pf-btn pf-btn-lg border border-white/20 text-white hover:border-cyan-300/60 hover:text-cyan-200">Explore the platform</a>
      </div>
      <p class="mt-5 font-mono text-[0.7rem] uppercase tracking-[0.16em] text-slate-400">14-day trial · No credit card · Live helpdesk inside</p>
    </div>
    <div class="pf-hero3d" id="service-engine" data-engine-state="idle" role="img" aria-label="Precision Service Engine: a 3D sculpture linking requests, technicians, assets and workflows">
      <div class="pf-hero-loader" id="service-engine-loader" aria-hidden="true" style="display:none">CALIBRATING</div>
      <div class="pf-hero3d-fallback" id="service-engine-fallback">
        @include('marketing.partials.engine-fallback')
      </div>
      <p class="pf-dial-caption" aria-hidden="true">Fig. 01 — Service engine · live render</p>
    </div>
  </div>
</section>
@push('scripts')
<script src="{{ asset('js/service-engine.js') }}" defer></script>
<script>
// Reveal-on-scroll (progressive enhancement; content visible without JS via .is-in on hero)
(function(){try{
  if(matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var els=document.querySelectorAll('.pf-reveal:not(.is-in)');
  if(!('IntersectionObserver' in window)){els.forEach(function(e){e.classList.add('is-in')});return;}
  var io=new IntersectionObserver(function(es){es.forEach(function(en){if(en.isIntersecting){en.target.classList.add('is-in');io.unobserve(en.target);}})},{threshold:.12});
  els.forEach(function(e){io.observe(e)});
}catch(e){}})();
</script>
@endpush

{{-- ============ 2 · Trust strip (capabilities only, no invented proof) ============ --}}
<section class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
  <div class="mx-auto max-w-7xl px-4 py-7 sm:px-6">
    <p class="pf-mono mb-4 text-[0.66rem] uppercase tracking-[0.2em] text-slate-500">One platform · every discipline</p>
    <div class="pf-trust">
      <span class="pf-trust-item"><x-icon name="ticket" /> Ticket management</span>
      <span class="pf-trust-item"><x-icon name="clock" /> SLA tracking</span>
      <span class="pf-trust-item"><x-icon name="globe" /> Customer portals</span>
      <span class="pf-trust-item"><x-icon name="device" /> Asset inventory</span>
      <span class="pf-trust-item"><x-icon name="chart" /> Service reporting</span>
      <span class="pf-trust-item"><x-icon name="shield" /> Tenant isolation</span>
    </div>
  </div>
</section>

{{-- ============ 3 · Product in action: request lifecycle ============ --}}
<section class="pf-section bg-white dark:bg-slate-950">
  <div class="mx-auto max-w-7xl px-4 sm:px-6">
    <div class="pf-section-head pf-reveal">
      <p class="pf-eyebrow">Product in action</p>
      <h2>From request to resolution, one connected thread.</h2>
      <p>Every record below mirrors a real screen in the application — submitted, triaged, assigned, resolved and reported, with the audit trail intact.</p>
    </div>
    <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
      <div class="pf-mini-ui pf-reveal" role="img" aria-label="Illustrated ticket lifecycle: submitted, triaged, assigned, resolved">
        <div class="pf-mini-ui-bar"><i></i><i></i><i></i><span class="ml-2 font-mono text-[0.66rem] tracking-widest text-slate-400">TICKET · #2841 · ILLUSTRATED SAMPLE</span></div>
        <div class="pf-mini-row"><x-badge color="blue">Submitted</x-badge><span>“VPN drops every afternoon” — via customer portal</span></div>
        <div class="pf-mini-row"><x-badge color="yellow">Triaged</x-badge><span>Priority High · Network queue · SLA 4h</span></div>
        <div class="pf-mini-row"><x-badge color="purple">Assigned</x-badge><span>Dana K. · first response 22 min</span></div>
        <div class="pf-mini-row"><x-badge color="green">Resolved</x-badge><span>Fix confirmed · CSAT 5/5 · time logged 1.4 h</span></div>
      </div>
      <div class="pf-rail pf-reveal">
        <div class="pf-rail-step"><span class="pf-rail-dot" aria-hidden="true"></span><h3 class="pf-display text-lg font-semibold">Submit anywhere</h3><p class="mt-1 text-slate-600 dark:text-slate-300">Portal, email-to-ticket or API. Duplicates merge; every event is timestamped.</p></div>
        <div class="pf-rail-step"><span class="pf-rail-dot" aria-hidden="true"></span><h3 class="pf-display text-lg font-semibold">Triage with context</h3><p class="mt-1 text-slate-600 dark:text-slate-300">Customer history, linked assets and the applicable SLA policy arrive with the ticket.</p></div>
        <div class="pf-rail-step"><span class="pf-rail-dot" aria-hidden="true"></span><h3 class="pf-display text-lg font-semibold">Assign and respond</h3><p class="mt-1 text-slate-600 dark:text-slate-300">Skills-based routing, collision-free assignment, public replies and private notes kept strictly separate.</p></div>
        <div class="pf-rail-step"><span class="pf-rail-dot" aria-hidden="true"></span><h3 class="pf-display text-lg font-semibold">Resolve and prove it</h3><p class="mt-1 text-slate-600 dark:text-slate-300">Resolution notes, satisfaction rating, billable time and the audit trail flow straight into reports.</p></div>
      </div>
    </div>
  </div>
</section>

{{-- ============ 4 · Feature showcase (editorial, alternating) ============ --}}
<section class="pf-section pf-dark-zone">
  <div class="mx-auto max-w-7xl px-4 sm:px-6">
    <div class="pf-section-head pf-reveal">
      <p class="pf-eyebrow">Capabilities</p>
      <h2 class="text-white">Engineered for the whole support operation.</h2>
    </div>
    <div class="space-y-16 lg:space-y-24">
      <div class="pf-feature-row pf-reveal">
        <div><h3 class="text-white">Intelligent ticket management</h3><p>Queues, SLAs, priorities, assignments, merges and watchers — with full-text search across every authorised record.</p><p class="mt-4"><a class="font-semibold text-cyan-300 hover:text-cyan-200" href="{{ route('features') }}#tickets">How ticketing works →</a></p></div>
        <div class="pf-mini-ui" role="img" aria-label="Illustrated queue sample"><div class="pf-mini-ui-bar"><i></i><i></i><i></i><span class="ml-2 font-mono text-[0.66rem] tracking-widest text-slate-400">QUEUE · SAMPLE</span></div><div class="pf-mini-row"><x-badge color="red">Urgent</x-badge><span>#2844 Exchange DAG unhealthy</span></div><div class="pf-mini-row"><x-badge color="yellow">High</x-badge><span>#2841 VPN drops afternoons</span></div><div class="pf-mini-row"><x-badge color="blue">Normal</x-badge><span>#2839 New starter laptop</span></div></div>
      </div>
      <div class="pf-feature-row is-flip pf-reveal">
        <div><h3 class="text-white">SLA monitoring and escalation</h3><p>Response and resolution targets per priority, business-hour calendars, pause on pending, breach warnings and automatic escalation.</p><p class="mt-4"><a class="font-semibold text-cyan-300 hover:text-cyan-200" href="{{ route('features') }}#sla">How SLAs work →</a></p></div>
        <div class="pf-mini-ui" role="img" aria-label="Illustrated SLA sample"><div class="pf-mini-ui-bar"><i></i><i></i><i></i><span class="ml-2 font-mono text-[0.66rem] tracking-widest text-slate-400">SLA · SAMPLE</span></div><div class="pf-mini-row"><x-badge color="green">On track</x-badge><span>First response · 62% elapsed</span></div><div class="pf-mini-row"><x-badge color="yellow">At risk</x-badge><span>Resolution · 84% elapsed</span></div><div class="pf-mini-row"><x-badge color="red">Breached</x-badge><span>Escalated to L2 · paged</span></div></div>
      </div>
      <div class="pf-feature-row pf-reveal">
        <div><h3 class="text-white">Customers, assets and teams</h3><p>Organisations with contacts and sites, a full device inventory with warranties, and technician skills matched to the work.</p><p class="mt-4"><a class="font-semibold text-cyan-300 hover:text-cyan-200" href="{{ route('features') }}#operations">Operations tour →</a></p></div>
        <div class="pf-mini-ui" role="img" aria-label="Illustrated asset sample"><div class="pf-mini-ui-bar"><i></i><i></i><i></i><span class="ml-2 font-mono text-[0.66rem] tracking-widest text-slate-400">ASSETS · SAMPLE</span></div><div class="pf-mini-row"><x-icon name="device" /><span>LT-1184 · Dell Latitude · Priya S.</span></div><div class="pf-mini-row"><x-icon name="device" /><span>SRV-02 · Warranty to Mar 2027</span></div><div class="pf-mini-row"><x-icon name="device" /><span>PRN-F2 · Toner 18%</span></div></div>
      </div>
      <div class="pf-feature-row is-flip pf-reveal">
        <div><h3 class="text-white">Automation, billing and proof</h3><p>Trigger-based workflows, billable time flowing into invoices, and reports the finance team will accept without a spreadsheet in between.</p><p class="mt-4"><a class="font-semibold text-cyan-300 hover:text-cyan-200" href="{{ route('pricing') }}">See plans →</a></p></div>
        <div class="pf-mini-ui" role="img" aria-label="Illustrated automation sample"><div class="pf-mini-ui-bar"><i></i><i></i><i></i><span class="ml-2 font-mono text-[0.66rem] tracking-widest text-slate-400">AUTOMATION · SAMPLE</span></div><div class="pf-mini-row"><x-icon name="bolt" /><span>Urgent + VIP → page on-call</span></div><div class="pf-mini-row"><x-icon name="bolt" /><span>Breach → escalate + notify</span></div><div class="pf-mini-row"><x-icon name="bolt" /><span>Resolved → request CSAT</span></div></div>
      </div>
    </div>
  </div>
</section>

{{-- ============ 5 · Connected workflow diagram ============ --}}
<section class="pf-section bg-white dark:bg-slate-950">
  <div class="mx-auto max-w-7xl px-4 sm:px-6">
    <div class="pf-section-head pf-reveal">
      <p class="pf-eyebrow">Connected workflow</p>
      <h2>One thread from signal to scorecard.</h2>
      <p>This is the implemented workflow — the same states your team will work in.</p>
    </div>
    <ol class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
      @foreach([['Customer request','Portal, email, API'],['Service desk','Triage + SLA clock'],['Assignment','Skills + workload'],['Context','Asset + history'],['Resolution','Notes + CSAT'],['Reporting','Time + audit']] as $i => $s)
      <li class="pf-card pf-card-flat pf-card-pad pf-reveal">
        <div class="pf-mono text-[0.66rem] tracking-[0.18em] text-cyan-700 dark:text-cyan-300">0{{ $i + 1 }}</div>
        <h3 class="pf-display mt-2 text-[0.95rem] font-semibold">{{ $s[0] }}</h3>
        <p class="mt-1 text-[0.82rem] text-slate-500 dark:text-slate-400">{{ $s[1] }}</p>
      </li>
      @endforeach
    </ol>
  </div>
</section>

{{-- ============ 6 · Organisations (tabbed use cases) ============ --}}
<section class="pf-section pf-dark-zone" x-data="{ tab: 'msp' }">
  <div class="mx-auto max-w-7xl px-4 sm:px-6">
    <div class="pf-section-head pf-reveal">
      <p class="pf-eyebrow">Who it serves</p>
      <h2 class="text-white">Designed for different organisations.</h2>
    </div>
    <div class="pf-reveal mb-8 flex flex-wrap gap-2" role="tablist" aria-label="Organisation types">
      <button class="pf-orgtab" role="tab" :aria-selected="tab==='msp'" @click="tab='msp'">Managed providers</button>
      <button class="pf-orgtab" role="tab" :aria-selected="tab==='internal'" @click="tab='internal'">Internal IT</button>
      <button class="pf-orgtab" role="tab" :aria-selected="tab==='consult'" @click="tab='consult'">Consultancies</button>
      <button class="pf-orgtab" role="tab" :aria-selected="tab==='smb'" @click="tab='smb'">SMBs</button>
    </div>
    <div class="pf-reveal max-w-3xl" x-show="tab==='msp'" role="tabpanel"><h3 class="pf-display text-xl font-semibold text-white">Managed service providers</h3><p class="mt-2 leading-relaxed text-slate-300">Run every client as an isolated organisation with its own SLAs, portals and invoices — while your engineers work one queue. Per-customer reporting makes QBRs write themselves.</p><p class="mt-4"><a class="font-semibold text-cyan-300" href="{{ route('use-cases') }}">MSP playbook →</a></p></div>
    <div class="max-w-3xl" x-show="tab==='internal'" role="tabpanel" x-cloak><h3 class="pf-display text-xl font-semibold text-white">Internal IT departments</h3><p class="mt-2 leading-relaxed text-slate-300">Give staff a portal they will actually use, keep every device and warranty in view, and show leadership resolution times instead of ticket counts.</p><p class="mt-4"><a class="font-semibold text-cyan-300" href="{{ route('use-cases') }}#internal-it">Internal IT guide →</a></p></div>
    <div class="max-w-3xl" x-show="tab==='consult'" role="tabpanel" x-cloak><h3 class="pf-display text-xl font-semibold text-white">IT consultancies</h3><p class="mt-2 leading-relaxed text-slate-300">Project-tagged tickets, billable time captured at the point of work, and client-ready invoices — evidence for every hour you bill.</p><p class="mt-4"><a class="font-semibold text-cyan-300" href="{{ route('use-cases') }}#consultancies">Consultancy guide →</a></p></div>
    <div class="max-w-3xl" x-show="tab==='smb'" role="tabpanel" x-cloak><h3 class="pf-display text-xl font-semibold text-white">Small and medium businesses</h3><p class="mt-2 leading-relaxed text-slate-300">Start on a modest plan, invite one technician, and grow into automation and SLAs only when they earn their keep.</p><p class="mt-4"><a class="font-semibold text-cyan-300" href="{{ route('pricing') }}">Start small →</a></p></div>
    <p class="mt-6"><a class="font-semibold text-cyan-300 hover:text-cyan-200" href="{{ route('use-cases') }}">Compare all use cases →</a></p>
  </div>
</section>

{{-- ============ 7 · Security and operational trust ============ --}}
<section class="pf-section bg-white dark:bg-slate-950">
  <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-2">
    <div class="pf-reveal">
      <p class="pf-eyebrow">Trust, stated plainly</p>
      <h2 class="pf-display text-3xl font-bold tracking-tight">What actually protects your data.</h2>
      <p class="mt-3 leading-relaxed text-slate-600 dark:text-slate-300">No certification badges we haven't earned. Just the controls the code enforces every request.</p>
      <p class="mt-5"><a href="{{ route('security') }}" class="pf-btn pf-btn-ghost">Read the security model</a></p>
    </div>
    <ul class="pf-reveal space-y-4">
      @foreach([['Tenant isolation, tested','Every query is scoped to your workspace; cross-tenant access is covered by automated attack tests.'],['Least-privilege roles','Owners, managers, technicians and customers each see only what their role permits.'],['Audit history','Status changes, assignments, payments and logins leave a permanent trail.'],['Secure customer access','Portals, signed downloads and optional two-factor authentication.']] as $t)
      <li class="flex gap-3"><span class="mt-0.5 text-cyan-700 dark:text-cyan-300"><x-icon name="check" /></span><div><strong class="font-semibold">{{ $t[0] }}</strong><p class="text-sm text-slate-600 dark:text-slate-300">{{ $t[1] }}</p></div></li>
      @endforeach
    </ul>
  </div>
</section>

{{-- ============ 8 · Pricing (real plan data) ============ --}}
<section class="pf-section pf-hairline-t bg-slate-50 dark:bg-slate-900/40">
  <div class="mx-auto max-w-7xl px-4 sm:px-6">
    <div class="pf-section-head pf-reveal">
      <p class="pf-eyebrow">Pricing</p>
      <h2>Plans priced like an instrument, not a lottery.</h2>
      <p>Live data from the subscription engine — the same plans, limits and intervals you will be billed on.</p>
    </div>
    <div class="grid gap-5 md:grid-cols-3">
      @foreach($plans as $plan)
      <div class="pf-card pf-card-pad pf-reveal {{ $loop->index === 1 ? '!border-cyan-600/50' : '' }}">
        <p class="pf-mono text-[0.66rem] uppercase tracking-[0.2em] text-slate-500">{{ $plan->name }}</p>
        <p class="pf-display mt-2 text-4xl font-bold">£{{ number_format($plan->price_monthly, 0) }}<span class="text-base font-medium text-slate-500">/mo</span></p>
        <p class="mt-1 text-sm text-slate-500">or £{{ number_format($plan->price_annual, 0) }}/yr · {{ $plan->currency }}</p>
        <ul class="mt-4 space-y-2 text-sm">
          <li class="flex gap-2"><x-icon name="check" /> {{ $plan->technician_seats }} technician seats</li>
          <li class="flex gap-2"><x-icon name="check" /> {{ number_format($plan->ticket_limit_monthly) }} tickets / month</li>
          <li class="flex gap-2"><x-icon name="check" /> {{ number_format($plan->asset_limit) }} assets</li>
          @foreach(array_slice($plan->features ?? [], 0, 4) as $f)<li class="flex gap-2"><x-icon name="check" /> {{ ucfirst(str_replace('_',' ',$f)) }}</li>@endforeach
        </ul>
        <a href="{{ route('register') }}" class="pf-btn {{ $loop->index === 1 ? 'pf-btn-primary' : 'pf-btn-ghost' }} mt-5 w-full justify-center">Start free trial</a>
      </div>
      @endforeach
    </div>
    <p class="mt-5 text-center text-sm"><a class="font-semibold text-cyan-700 hover:underline dark:text-cyan-300" href="{{ route('pricing') }}">Full comparison with audiences and intervals →</a></p>
  </div>
</section>

{{-- ============ 9 · Final conversion ============ --}}
<section class="pf-dark-zone pf-grid-lines">
  <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
    <div class="pf-reveal max-w-2xl border-l-2 border-cyan-400 pl-6 sm:pl-10">
      <p class="pf-eyebrow">Begin</p>
      <h2 class="pf-display text-3xl font-bold text-white sm:text-[2.6rem] sm:leading-[1.05]">Put your support operation on precise footing.</h2>
      <p class="mt-4 text-slate-300">Fourteen days, your own workspace, your real tickets. Keep everything or walk away clean.</p>
      <div class="mt-7 flex flex-wrap gap-3">
        <a href="{{ route('register') }}" class="pf-btn pf-btn-bright pf-btn-lg">Start free trial <x-icon name="arrow" /></a>
        <a href="{{ route('contact') }}" class="pf-btn pf-btn-lg border border-white/20 text-white hover:border-cyan-300/60">Talk to us</a>
      </div>
    </div>
  </div>
</section>
@endsection
