@extends('layouts.app')
@section('title', 'Leads')
@section('nav', 'leads.index')

@section('content')
<div class="ph">
  <div><h1>Leads</h1><div class="sub">Prospects being worked by the sales team</div></div>
  <div><a class="btn pri" href="{{ route('leads.create') }}">+ New lead</a></div>
</div>

<form class="filters" method="GET">
  <input type="search" name="q" placeholder="Search company, contact, phone, email…" value="{{ request('q') }}">
  <x-filter-select name="prio" :options="\App\Support\Crm::PRIOS" label="All temperatures" />
  <x-filter-select name="status" :options="\App\Support\Crm::LSTATUS" label="All statuses" />
  @if ($isAdmin)
  <x-filter-select name="agent" :options="$agentList" label="All agents" />
  @endif
  <x-filter-select name="src" :options="\App\Support\Crm::SOURCES" label="All sources" />
  <label><input type="checkbox" name="arch" value="1" @checked(request('arch'))> Show archived</label>
</form>

@if ($leads->isEmpty())
  <div class="empty">No leads match. Click “+ New lead” to add one.</div>
@else
  <div class="tw"><table>
    <thead><tr><th>ID</th><th>Company</th><th>Category</th><th>Contact</th><th>Phone / WhatsApp</th><th>Source</th><th>Temp.</th><th>Status</th><th>Agent</th><th>Next follow-up</th></tr></thead>
    <tbody>
      @foreach ($leads as $c)
        @php($next = $c->next_due ? \Illuminate\Support\Carbon::parse($c->next_due) : null)
        <tr data-act="go" data-href="{{ route('companies.show', $c) }}">
          <td><span class="mut nw">{{ $c->code }}</span></td>
          <td><a class="lnk" href="{{ route('companies.show', $c) }}">{{ $c->name }}</a></td>
          <td>{{ $c->category ?: '—' }}</td>
          <td>{{ $c->contact ?: '—' }}</td>
          <td>{{ $c->phone ?: ($c->whatsapp ?: '—') }}</td>
          <td>{{ $c->source ?: '—' }}</td>
          <td><x-prio :p="$c->priority" /></td>
          <td><x-badge :text="$c->status" /></td>
          <td>{{ $c->agent ?: '—' }}</td>
          <td>@if ($next)<span class="nw" @if ($next->isPast()) style="color:var(--bad);font-weight:600" @endif>{{ $next->format('j M Y, H:i') }}</span>@else — @endif</td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
@endif
@include('partials.pager', ['p' => $leads])
@endsection
