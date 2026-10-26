@extends('layouts.app')
@php
  use App\Support\Crm;
  $p = $payment;
  $editing = $p->exists;
  $back = $p->invoice_id ? route('invoices.edit', $p->invoice_id) : route('documents.index', ['type' => 'Payment']);
@endphp
@section('title', $editing ? 'Payment '.$p->code : 'Record payment')
@section('nav', 'documents.index')

@section('content')
<div class="ph">
  <div>
    <div class="sub"><a class="lnk" href="{{ $back }}">← {{ $p->invoice?->code ?? 'Documents' }}</a></div>
    <h1>{{ $editing ? 'Payment '.$p->code : 'Record payment' }}</h1>
  </div>
  @if ($editing)
    <div class="btns">
      <a class="btn sm" href="{{ route('pdf.receipt', $p) }}" target="_blank">View receipt</a>
      <a class="btn sm" href="{{ route('pdf.receipt', [$p, 'download' => 1]) }}">Download receipt</a>
    </div>
  @endif
</div>

@if (! $invoices)
  <div class="empty">There are no issued invoices with a balance due. Create or issue an invoice first.</div>
@else
<form class="card formcard" style="max-width:760px" method="POST" action="{{ $editing ? route('payments.update', $p) : route('payments.store') }}" data-invoices='@json($invoices)' data-pay>
  @csrf
  @if ($editing) @method('PUT') @endif

  <x-f.select label="Invoice" name="invoice_id" :options="array_map(fn ($i) => $i['label'], $invoices)" :value="$p->invoice_id" blank="Select invoice…" assoc req />
  <div class="hint" id="payhint" style="margin-bottom:12px"></div>
  <div class="g3">
    <x-f.text label="Payment date" name="date" type="date" :value="$p->date?->format('Y-m-d')" req />
    <x-f.select label="Payment method" name="method" :options="Crm::PAYM" :value="$p->method" />
    <x-f.text label="Amount" name="amount" type="number" step="any" min="0" :value="$p->amount" req />
    <x-f.text label="Reference no." name="reference" :value="$p->reference" />
    <x-f.select label="Status" name="status" :options="Crm::PAYSTATUS" :value="$p->status" />
    <span></span>
  </div>
  <x-f.area label="Notes" name="notes" :value="$p->notes" rows="2" />

  <div class="mf">
    <a class="btn" href="{{ $back }}">Cancel</a>
    <button class="btn pri">Save</button>
  </div>
</form>
@endif
@endsection
