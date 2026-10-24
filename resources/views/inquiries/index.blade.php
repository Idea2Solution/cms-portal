@extends('layouts.app')
@section('title', 'Inquiries')
@section('nav', 'inquiries.index')
@php($money = fn ($n) => preg_replace('/\.00$/', '', \App\Support\Crm::money($n)))

@section('content')
<div class="ph">
  <div><h1>Inquiries</h1><div class="sub">What each lead or customer needs from us</div></div>
  <div><a class="btn pri" href="{{ route('inquiries.create') }}">+ New inquiry</a></div>
</div>

<form class="filters" method="GET">
  <input type="search" name="q" placeholder="Search inquiries…" value="{{ request('q') }}">
  <x-filter-select name="status" :options="\App\Support\Crm::ISTATUS" label="All statuses" />
  <x-filter-select name="prio" :options="\App\Support\Crm::PRIOS" label="All temperatures" />
  <x-filter-select name="cat" :options="array_keys(\App\Support\Crm::setting('services'))" label="All services" />
</form>

@if ($inquiries->isEmpty())
  <div class="empty">No inquiries yet.</div>
@else
  <div class="tw"><table>
    <thead><tr><th>ID</th><th>Date</th><th>Company</th><th>Service</th><th>Requirement</th><th>Budget</th><th>Temp.</th><th>Status</th><th>Agent</th></tr></thead>
    <tbody>
      @foreach ($inquiries as $i)
        <tr data-act="go" data-href="{{ route('inquiries.edit', $i) }}">
          <td><span class="mut nw">{{ $i->code }}</span></td>
          <td><span class="nw">{{ $i->date?->format('j M Y') ?? '—' }}</span></td>
          <td><a class="lnk" href="{{ route('companies.show', $i->company_id) }}">{{ $i->company->name }}</a></td>
          <td><b>{{ $i->service }}</b><br><small class="mut">{{ $i->category }}</small></td>
          <td>{{ Str::limit($i->requirement, 70) }}</td>
          <td>
            @if ($i->budget_from || $i->budget_to)
              <span class="nw">{{ $money($i->budget_from) }}{{ $i->budget_to ? ' – '.$money($i->budget_to) : '' }}</span>
            @else — @endif
          </td>
          <td><x-prio :p="$i->priority" /></td>
          <td><x-badge :text="$i->status" /></td>
          <td>{{ $i->agent ?: '—' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
@endif
@include('partials.pager', ['p' => $inquiries])
@endsection
