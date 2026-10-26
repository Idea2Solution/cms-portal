@extends('layouts.app')
@php
  use App\Support\Crm;
  use App\Support\CompanyOptions;
  $p = $proposal;
  $editing = $p->exists;
  $cid = old('company_id', $p->company_id);
  $inqOptions = collect($companies[$cid]['inquiries'] ?? [])->pluck('label', 'id')->all();
  $back = $p->company_id ? route('companies.show', $p->company_id) : route('documents.index', ['type' => 'Proposal']);
@endphp
@section('title', $editing ? 'Proposal '.$p->code : 'New proposal')
@section('nav', 'documents.index')
@if ($p->company_id) @section('company_id', $p->company_id) @endif

@section('content')
<div class="ph">
  <div>
    <div class="sub"><a class="lnk" href="{{ $back }}">← {{ $p->company?->name ?? 'Documents' }}</a></div>
    <h1>{{ $editing ? 'Proposal '.$p->code : 'New proposal' }}</h1>
    @if ($editing)<div style="margin-top:6px"><x-badge :text="$p->status" /> <span class="mut">Total {{ Crm::money($p->total, $p->currency) }}</span></div>@endif
  </div>
  @if ($editing)
    <div class="btns">
      <a class="btn sm" href="{{ route('pdf.proposal', $p) }}" target="_blank">View PDF</a>
      <a class="btn sm" href="{{ route('pdf.proposal', [$p, 'download' => 1]) }}">Download PDF</a>
      @if ($p->status === 'Accepted')
        <a class="btn sm pri" href="{{ route('invoices.create', ['proposal' => $p->id]) }}">Create invoice</a>
      @endif
      @if ($isAdmin)
        <form class="inline" method="POST" action="{{ route('proposals.destroy', $p) }}" data-confirm="Delete proposal {{ $p->code }}?">@csrf @method('DELETE')<button class="btn sm danger">Delete</button></form>
      @endif
    </div>
  @endif
</div>

<form class="card formcard" method="POST" action="{{ $editing ? route('proposals.update', $p) : route('proposals.store') }}" data-companies='@json($companies)' data-items>
  @csrf
  @if ($editing) @method('PUT') @endif

  <div class="g4">
    <x-f.select label="Lead / customer" name="company_id" :options="CompanyOptions::labels($companies)" :value="$p->company_id" blank="Select…" assoc req />
    <x-f.select label="Inquiry" name="inquiry_id" :options="$inqOptions" :value="$p->inquiry_id" blank="— none —" assoc />
    <x-f.select label="Sales agent" name="agent" :options="Crm::assignable()" :value="$p->agent" />
    <x-f.select label="Status" name="status" :options="Crm::PSTATUS" :value="$p->status" />
  </div>
  <div class="g4">
    <x-f.text label="Proposal date" name="date" type="date" :value="$p->date?->format('Y-m-d')" req />
    <x-f.text label="Valid until" name="valid_until" type="date" :value="$p->valid_until?->format('Y-m-d')" />
    <x-f.select label="Currency" name="currency" :options="Crm::CURS" :value="$p->currency" />
    <x-f.text label="Tax %" name="tax_pct" type="number" step="any" min="0" :value="$p->tax_pct" />
  </div>

  @include('partials.items', ['items' => $p->items])

  <div class="g4" style="margin-top:12px">
    <x-f.text label="Discount (amount)" name="discount" type="number" step="any" min="0" :value="$p->discount" />
    <span></span><span></span><span></span>
  </div>
  <x-f.area label="Payment terms / commercial notes" name="terms" :value="$p->terms" rows="2" />
  <x-f.area label="Internal notes" name="notes" :value="$p->notes" rows="2" />
  <p class="hint">Sent / Negotiation / Accepted / Rejected update the linked inquiry automatically.</p>

  <div class="mf">
    <a class="btn" href="{{ $back }}">Cancel</a>
    <button class="btn pri">Save</button>
  </div>
</form>

@if ($editing && $p->invoices->isNotEmpty())
  <div class="card formcard" style="margin-top:14px"><h3>Invoices from this proposal</h3>
    @foreach ($p->invoices as $inv)
      <div class="li"><a class="lnk" href="{{ route('invoices.edit', $inv) }}">{{ $inv->code }}</a></div>
    @endforeach
  </div>
@endif
@endsection
