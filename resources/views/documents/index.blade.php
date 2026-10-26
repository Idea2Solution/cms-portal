@extends('layouts.app')
@section('title', 'Documents')
@section('nav', 'documents.index')

@section('content')
@php
  $newRoutes = [
    'EoI' => route('documents.create', ['type' => 'EoI']),
    'RFQ' => route('documents.create', ['type' => 'RFQ']),
    'Proposal' => route('proposals.create'),
    'Invoice' => route('invoices.create'),
    'Payment' => route('payments.create'),
  ];
  $tones = ['Invoice' => 'brand', 'Payment' => 'good'];
@endphp
<div class="ph">
  <div><h1>Documents</h1><div class="sub">Expressions of interest, RFQs, proposals, invoices and payments in one place</div></div>
  <div class="btns">
    @foreach ($newRoutes as $t => $href)
      <a @class(['btn', 'sm', 'pri' => $t === 'Invoice']) href="{{ $href }}">+ {{ $t }}</a>
    @endforeach
  </div>
</div>

<form class="filters" method="GET">
  <input type="hidden" name="type" value="{{ $type }}">
  <div class="tabs" style="margin:0">
    <a href="{{ route('documents.index', array_filter(['q' => request('q')])) }}" @class(['tab', 'on' => ! $type]) style="text-decoration:none">All<i>{{ $total }}</i></a>
    @foreach (\App\Support\Crm::DOC_TYPES as $t)
      <a href="{{ route('documents.index', array_filter(['type' => $t, 'q' => request('q')])) }}" @class(['tab', 'on' => $type === $t]) style="text-decoration:none">{{ $t }}<i>{{ $counts[$t] ?? 0 }}</i></a>
    @endforeach
  </div>
  <span class="spacer"></span>
  <input type="search" name="q" placeholder="Search…" value="{{ request('q') }}">
</form>

@if ($docs->isEmpty())
  <div class="empty">No documents yet.</div>
@else
  <div class="tw"><table>
    <thead><tr><th>Type</th><th>Ref</th><th>Company</th><th>Date</th><th class="r">Amount</th><th>Status</th><th>Note</th><th></th></tr></thead>
    <tbody>
      @foreach ($docs as $d)
        <tr data-act="go" data-href="{{ $d['url'] }}">
          <td><x-badge :text="$d['type']" :tone="$tones[$d['type']] ?? 'info'" /></td>
          <td><b class="nw">{{ $d['ref'] }}</b></td>
          <td><a class="lnk" href="{{ route('companies.show', $d['company_id']) }}">{{ $d['company'] }}</a></td>
          <td class="nw">{{ $d['date']?->format('j M Y') ?? '—' }}</td>
          <td class="r nw">{{ $d['amount'] ?: '—' }}</td>
          <td><x-badge :text="$d['status']" /></td>
          <td><span class="mut">{{ $d['note'] }}</span></td>
          <td class="nw">@if ($d['pdf'])<a class="btn sm" href="{{ $d['pdf'] }}" target="_blank">PDF</a>@endif</td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
@endif
@include('partials.pager', ['p' => $docs])
@endsection
