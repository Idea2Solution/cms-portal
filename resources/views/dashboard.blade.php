@extends('layouts.app')
@section('title', 'Dashboard')
@section('nav', 'dashboard')

@section('content')
<div class="ph">
  <div><h1>Dashboard</h1><div class="sub">Your sales day at a glance</div></div>
  <div class="tabs" style="margin:0">
    @foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days', 0 => 'All time'] as $v => $l)
      <a href="{{ route('dashboard', ['period' => $v]) }}" @class(['tab', 'on' => $period === $v]) style="text-decoration:none">{{ $l }}</a>
    @endforeach
  </div>
</div>

@if ($empty)
  <div class="note"><b>Welcome!</b> Add your first lead with “+ New”, or run <code>php artisan db:seed --class=DemoSeeder</code> to load a few demo records.</div>
@endif

@php
  $kpi = fn ($label, $value, $href, $cls = '') => '<a class="kpi '.$cls.'" href="'.$href.'" style="text-decoration:none"><b>'.e($value).'</b><span>'.e($label).'</span></a>';
@endphp
<div class="kpis">
  {!! $kpi("New leads ($label)", $kpis['newLeads'], route('leads.index')) !!}
  {!! $kpi('Hot leads', $kpis['hot'], route('leads.index', ['prio' => 'Hot']), 'hot') !!}
  {!! $kpi('Not contacted', $kpis['notContacted'], route('leads.index', ['status' => 'Not Contacted'])) !!}
  {!! $kpi('Overdue follow-ups', $kpis['overdue'], route('followups.index', ['tab' => 'overdue']), $kpis['overdue'] ? 'alert' : '') !!}
  {!! $kpi("Today's follow-ups", $kpis['today'], route('followups.index', ['tab' => 'due'])) !!}
  {!! $kpi('Open inquiries', $kpis['openInquiries'], route('inquiries.index')) !!}
  {!! $kpi("Won / Lost ($label)", $kpis['won'].' / '.$kpis['lost'], route('inquiries.index', ['status' => 'Won'])) !!}
  @if (\App\Support\Crm::customersOn())
    {!! $kpi('Customers', $kpis['customers'], route('customers.index')) !!}
  @endif
</div>

<div class="card" style="margin-bottom:14px">
  <h3>Pipeline <span class="mut" style="font-weight:500;font-size:12.5px">Stages update automatically from lead status and inquiries</span></h3>
  <div class="strip">
    @foreach ($stages as $s => $n)
      <div><b>{{ $n }}</b><span>{{ $s }}</span></div>
    @endforeach
  </div>
</div>

<div class="grid2">
  <div class="card"><h3>Follow-ups needing action <a class="lnk" href="{{ route('followups.index') }}" style="font-size:12.5px">All →</a></h3>
    @forelse ($followups as $f)
      <div class="li">
        <div><a class="lnk" href="{{ route('companies.show', $f->company_id) }}">{{ $f->company->name }}</a>
          <small>{{ $f->purpose ?: $f->method }} · {{ $f->method }} · {{ $f->agent }}</small></div>
        <div class="r nw">@if ($f->isOverdue())<x-badge text="Overdue" />@endif
          <small>{{ $f->due->format('j M Y, H:i') }}</small>
          @include('followups._done-btn', ['f' => $f])</div>
      </div>
    @empty
      <div class="empty">No follow-ups due. 🎉</div>
    @endforelse
  </div>
  <div class="card"><h3>Hot leads to work</h3>
    @forelse ($hotLeads as $c)
      <div class="li">
        <div><a class="lnk" href="{{ route('companies.show', $c) }}">{{ $c->name }}</a><small>{{ $c->contact }} · {{ $c->phone }}</small></div>
        <div class="r nw"><x-badge :text="$c->status" /><small>Last contact: {{ $c->last_contacted?->format('j M Y') ?? '—' }}</small></div>
      </div>
    @empty
      <div class="empty">No hot leads yet.</div>
    @endforelse
  </div>
</div>
@endsection
