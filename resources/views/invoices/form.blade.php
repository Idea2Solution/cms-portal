@extends('layouts.app')
@php
  use App\Support\Crm;
  use App\Support\CompanyOptions;
  $i = $invoice;
  $editing = $i->exists;
  $cid = old('company_id', $i->company_id);
  $propOptions = collect($companies[$cid]['proposals'] ?? [])->pluck('label', 'id')->all();
  $back = $i->company_id ? route('companies.show', $i->company_id) : route('documents.index', ['type' => 'Invoice']);
@endphp
@section('title', $editing ? 'Invoice '.$i->code : 'New invoice')
@section('nav', 'documents.index')
@if ($i->company_id) @section('company_id', $i->company_id) @endif

@section('content')
<div class="ph">
  <div>
    <div class="sub"><a class="lnk" href="{{ $back }}">← {{ $i->company?->name ?? 'Documents' }}</a></div>
    <h1>{{ $editing ? 'Invoice '.$i->code : 'New invoice' }}</h1>
  </div>
  @if ($editing)
    <div class="btns">
      <a class="btn sm" href="{{ route('pdf.invoice', $i) }}" target="_blank">View PDF</a>
      <a class="btn sm" href="{{ route('pdf.invoice', [$i, 'download' => 1]) }}">Download PDF</a>
      @if ($i->state === 'Issued' && $i->balance() > 0)
        <a class="btn sm pri" href="{{ route('payments.create', ['invoice' => $i->id]) }}">Record payment</a>
      @endif
      @if ($isAdmin)
        <form class="inline" method="POST" action="{{ route('invoices.destroy', $i) }}" data-confirm="Delete invoice {{ $i->code }} and its payments? Prefer setting the state to Cancelled.">@csrf @method('DELETE')<button class="btn sm danger">Delete</button></form>
      @endif
    </div>
  @endif
</div>

<form class="card formcard" method="POST" action="{{ $editing ? route('invoices.update', $i) : route('invoices.store') }}" data-companies='@json($companies)' data-items @unless ($editing) data-newinv @endunless>
  @csrf
  @if ($editing) @method('PUT') @endif

  @if ($editing)
    <div class="note">Status: <b>{{ $i->status() }}</b> · Total {{ Crm::money($i->total, $i->currency) }} · Paid {{ Crm::money($i->paid(), $i->currency) }} · <b>Balance {{ Crm::money($i->balance(), $i->currency) }}</b></div>
  @endif

  <div class="g4">
    <x-f.select label="Company" name="company_id" :options="CompanyOptions::labels($companies)" :value="$i->company_id" blank="Select…" assoc req />
    <x-f.select label="Proposal" name="proposal_id" :options="$propOptions" :value="$i->proposal_id" blank="— none —" assoc />
    <x-f.text label="Project / scope" name="project" :value="$i->project" />
    <x-f.select label="Sales agent" name="agent" :options="Crm::assignable()" :value="$i->agent" />
  </div>
  <div class="g4">
    <x-f.text label="Invoice date" name="date" type="date" :value="$i->date?->format('Y-m-d')" req />
    <x-f.text label="Due date" name="due" type="date" :value="$i->due?->format('Y-m-d')" />
    <x-f.select label="Currency" name="currency" :options="Crm::CURS" :value="$i->currency" />
    <x-f.text label="Tax %" name="tax_pct" type="number" step="any" min="0" :value="$i->tax_pct" />
  </div>
  <div class="g4">
    <x-f.select label="Invoice state" name="state" :options="Crm::ISTATES" :value="$i->state" />
    <span></span><span></span><span></span>
  </div>

  @include('partials.items', ['items' => $i->items, 'paid' => $editing ? $i->paid() : null])

  <div class="g4" style="margin-top:12px">
    <x-f.text label="Discount (amount)" name="discount" type="number" step="any" min="0" :value="$i->discount" />
    <span></span><span></span><span></span>
  </div>
  <x-f.area label="Payment terms / notes on invoice" name="terms" :value="$i->terms" rows="2" />
  <x-f.area label="Internal notes" name="notes" :value="$i->notes" rows="2" />

  <div class="mf">
    <a class="btn" href="{{ $back }}">Cancel</a>
    <button class="btn pri">Save</button>
  </div>
</form>

@if ($editing)
  <div class="card formcard" style="margin-top:14px">
    <h3>Payments received
      @if ($i->state === 'Issued' && $i->balance() > 0)<a class="btn sm" href="{{ route('payments.create', ['invoice' => $i->id]) }}">+ Record payment</a>@endif
    </h3>
    @forelse ($i->payments as $pay)
      <div class="li" data-act="go" data-href="{{ route('payments.edit', $pay) }}" style="cursor:pointer">
        <div><b>{{ $pay->code }}</b> · {{ $pay->date->format('j M Y') }} · {{ $pay->method }}{{ $pay->reference ? ' · '.$pay->reference : '' }}</div>
        <div class="nw">{{ Crm::money($pay->amount, $i->currency) }} <x-badge :text="$pay->status" />
          <a class="btn sm" href="{{ route('pdf.receipt', $pay) }}" target="_blank">Receipt</a></div>
      </div>
    @empty
      <div class="mut" style="padding:6px 0">No payments yet.</div>
    @endforelse
  </div>
@endif
@endsection
