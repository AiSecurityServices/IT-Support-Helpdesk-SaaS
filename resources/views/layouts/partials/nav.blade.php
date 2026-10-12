{{-- Primary navigation (shared desktop + mobile). Staff links only; customers use the portal layout. --}}
@php $u = auth()->user(); @endphp
@php
$link = 'pf-navlink';
$on = fn($cond) => $cond ? 'pf-navlink is-active' : 'pf-navlink';
@endphp
<div class="pf-navsection">Operate</div>
<a href="{{ route('dashboard') }}" class="{{ $on(request()->routeIs('dashboard')) }}"><x-icon name="dashboard" /> Dashboard</a>
<a href="{{ route('tickets.index') }}" class="{{ $on(request()->routeIs('tickets.*')) }}"><x-icon name="ticket" /> Tickets</a>
<a href="{{ route('customers.index') }}" class="{{ $on(request()->routeIs('customers.*')) }}"><x-icon name="users" /> Customers</a>
<a href="{{ route('assets.index') }}" class="{{ $on(request()->routeIs('assets.*')) ? 'pf-navlink is-active' : 'pf-navlink' }}"><x-icon name="device" /> Assets</a>
@if(feature('knowledge_base'))
<a href="{{ route('kb.index') }}" class="{{ $on(request()->routeIs('kb.*')) }}"><x-icon name="book" /> Knowledge base</a>
@endif
@if(feature('service_catalog'))
<a href="{{ route('catalog.index') }}" class="{{ $on(request()->routeIs('catalog.*')) }}"><x-icon name="layers" /> Service catalogue</a>
@endif
<div class="pf-navsection">Deliver</div>
@if($u && $u->canManage('teams'))
<a href="{{ route('teams.index') }}" class="{{ $on(request()->routeIs('teams.*')) }}"><x-icon name="users" /> Teams</a>
<a href="{{ route('technicians.index') }}" class="{{ $on(request()->routeIs('technicians.*')) }}"><x-icon name="wrench" /> Technicians</a>
@endif
<a href="{{ route('remote.index') }}" class="{{ $on(request()->routeIs('remote.*')) }}"><x-icon name="globe" /> Remote support</a>
@if(feature('problems'))
<a href="{{ route('problems.index') }}" class="{{ $on(request()->routeIs('problems.*')) }}"><x-icon name="search" /> Problems</a>
@endif
@if(feature('changes'))
<a href="{{ route('changes.index') }}" class="{{ $on(request()->routeIs('changes.*')) }}"><x-icon name="swap" /> Changes</a>
@endif
@if($u && $u->hasRole('owner','admin','manager') && feature('major_incidents'))
<a href="{{ route('incidents.index') }}" class="{{ $on(request()->routeIs('incidents.*')) }}"><x-icon name="alert" /> Major incidents</a>
@endif
@if(feature('cmdb'))
<a href="{{ route('cmdb.index') }}" class="{{ $on(request()->routeIs('cmdb.*')) }}"><x-icon name="box" /> CMDB</a>
@endif
@if($u && $u->hasRole('owner','admin','manager') && feature('automation'))
<a href="{{ route('automation.index') }}" class="{{ $on(request()->routeIs('automation.*')) }}"><x-icon name="bolt" /> Automation</a>
@endif
<div class="pf-navsection">Manage</div>
@if($u && $u->canManage('invoices'))
<a href="{{ route('invoices.index') }}" class="{{ $on(request()->routeIs('invoices.*')) }}"><x-icon name="card" /> Invoices</a>
@endif
@if($u && $u->canManage('reports'))
<a href="{{ route('reports.index') }}" class="{{ $on(request()->routeIs('reports.*')) }}"><x-icon name="chart" /> Reports</a>
@endif
@if($u && $u->hasRole('owner','admin'))
<a href="{{ route('billing.index') }}" class="{{ $on(request()->routeIs('billing.*')) }}"><x-icon name="card" /> Subscription</a>
<a href="{{ route('settings.index') }}" class="{{ $on(request()->routeIs('settings.*','sla.*')) }}"><x-icon name="sliders" /> Settings</a>
@endif
