@extends('layouts.marketing')
@section('title', 'Resources: guides, documentation and answers')
@section('content')
@include('marketing.partials.pagehead', ['eyebrow' => 'Resources', 'slot' => 'Learn the craft of support operations.', 'lede' => 'Guides, answers and product documentation — written for the people who run the queue.'])
<section class="pf-section bg-white dark:bg-slate-950">
  <div class="mx-auto grid max-w-7xl gap-5 px-4 sm:px-6 md:grid-cols-3">
    <a href="{{ route('faq') }}" class="pf-card pf-card-flat pf-card-pad block transition hover:-translate-y-0.5"><span class="text-cyan-700 dark:text-cyan-300"><x-icon name="book" class="pf-icon-lg" /></span><h2 class="pf-display mt-3 font-semibold">Frequently asked questions</h2><p class="mt-1.5 text-sm text-slate-600 dark:text-slate-300">Trials, seats, migrations, portals and billing — answered directly.</p></a>
    <a href="{{ route('use-cases') }}" class="pf-card pf-card-flat pf-card-pad block transition hover:-translate-y-0.5"><span class="text-cyan-700 dark:text-cyan-300"><x-icon name="layers" class="pf-icon-lg" /></span><h2 class="pf-display mt-3 font-semibold">Playbooks by organisation</h2><p class="mt-1.5 text-sm text-slate-600 dark:text-slate-300">How MSPs, internal teams, consultancies and SMBs set up for success.</p></a>
    <a href="{{ route('security') }}" class="pf-card pf-card-flat pf-card-pad block transition hover:-translate-y-0.5"><span class="text-cyan-700 dark:text-cyan-300"><x-icon name="shield" class="pf-icon-lg" /></span><h2 class="pf-display mt-3 font-semibold">Security model</h2><p class="mt-1.5 text-sm text-slate-600 dark:text-slate-300">Isolation, access control and audit — with evidence, not adjectives.</p></a>
    <a href="{{ route('contact') }}" class="pf-card pf-card-flat pf-card-pad block transition hover:-translate-y-0.5"><span class="text-cyan-700 dark:text-cyan-300"><x-icon name="send" class="pf-icon-lg" /></span><h2 class="pf-display mt-3 font-semibold">Talk to an engineer</h2><p class="mt-1.5 text-sm text-slate-600 dark:text-slate-300">Migration and workflow questions answered by people who run helpdesks.</p></a>
    <a href="{{ route('pricing') }}" class="pf-card pf-card-flat pf-card-pad block transition hover:-translate-y-0.5"><span class="text-cyan-700 dark:text-cyan-300"><x-icon name="card" class="pf-icon-lg" /></span><h2 class="pf-display mt-3 font-semibold">Plans and limits</h2><p class="mt-1.5 text-sm text-slate-600 dark:text-slate-300">Live plan data, limits and billing intervals from the subscription engine.</p></a>
    <div class="pf-card pf-card-flat pf-card-pad !border-cyan-600/40"><h2 class="pf-display mt-0 font-semibold">Start your workspace</h2><p class="mt-1.5 text-sm text-slate-600 dark:text-slate-300">Fourteen days, your real tickets, no credit card.</p><p class="mt-4"><a href="{{ route('register') }}" class="pf-btn pf-btn-primary pf-btn-sm">Start free trial</a></p></div>
  </div>
</section>
@endsection
