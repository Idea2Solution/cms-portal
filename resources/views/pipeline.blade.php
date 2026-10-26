@extends('layouts.app')
@section('title', 'Pipeline')
@section('nav', 'pipeline')

@section('content')
<div class="ph">
  <div><h1>Pipeline</h1><div class="sub">Stages update automatically from lead status, inquiries and proposals — nothing to drag or re-enter. Won/Lost show the last 30 days.</div></div>
  @if ($isAdmin)
    <form class="filters" method="GET" style="margin:0">
      <x-filter-select name="agent" :options="$agentList" label="All agents" />
    </form>
  @endif
</div>

<div class="board">
  @foreach ($board as $stage => $cards)
    <div class="col">
      <h4>{{ $stage }}<small>{{ $cards->count() }}</small></h4>
      @forelse ($cards as ['company' => $c, 'value' => $value])
        @php($inq = $c->inquiries->first(fn ($i) => $i->isOpen()) ?? $c->inquiries->first())
        <div class="ck {{ strtolower($c->priority ?? '') }}" data-act="go" data-href="{{ route('companies.show', $c) }}">
          <b>{{ $c->name }}</b>
          <small>{{ $inq?->service ?? $c->category }}</small>
          <div style="display:flex;justify-content:space-between;margin-top:5px;font-size:12px">
            <span><x-prio :p="$c->priority" /></span><span class="mut">{{ $c->agent }}</span>
          </div>
          @if ($value)<div style="margin-top:4px;font-weight:600;font-size:12.5px">{{ $value }}</div>@endif
        </div>
      @empty
        <div class="mut" style="font-size:12px;padding:6px">—</div>
      @endforelse
    </div>
  @endforeach
</div>
@endsection
