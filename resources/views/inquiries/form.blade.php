@extends('layouts.app')
@php
  use App\Support\Crm;
  $editing = $inquiry->exists;
  $services = Crm::setting('services');
  $cat = old('category', $inquiry->category);
  $back = $inquiry->company_id ? route('companies.show', $inquiry->company_id) : route('inquiries.index');
@endphp
@section('title', $editing ? 'Inquiry '.$inquiry->code : 'New inquiry')
@section('nav', 'inquiries.index')
@if ($inquiry->company_id) @section('company_id', $inquiry->company_id) @endif

@section('content')
<div class="ph">
  <div>
    <div class="sub"><a class="lnk" href="{{ $back }}">← {{ $inquiry->company?->name ?? 'Inquiries' }}</a></div>
    <h1>{{ $editing ? 'Inquiry '.$inquiry->code : 'New inquiry' }}</h1>
  </div>
  @if ($editing)
    <div class="btns">
      <a class="btn sm" href="{{ route('followups.create', ['company' => $inquiry->company_id, 'inquiry' => $inquiry->id]) }}">+ Follow-up</a>
      <form class="inline" method="POST" action="{{ route('inquiries.destroy', $inquiry) }}" data-confirm="Delete inquiry {{ $inquiry->code }}?">@csrf @method('DELETE')<button class="btn sm danger">Delete</button></form>
    </div>
  @endif
</div>

<form class="card formcard" method="POST" action="{{ $editing ? route('inquiries.update', $inquiry) : route('inquiries.store') }}" data-services='@json($services)'>
  @csrf
  @if ($editing) @method('PUT') @endif

  <div class="g3">
    <x-f.select label="Lead / customer" name="company_id" :options="$companies" :value="$inquiry->company_id" blank="Select…" assoc req />
    <x-f.text label="Inquiry date" name="date" type="datetime-local" :value="$inquiry->date?->format('Y-m-d\TH:i')" />
    <x-f.select label="Inquiry source" name="source" :options="Crm::SOURCES" :value="$inquiry->source" blank="—" />
    <x-f.select label="Sales agent" name="agent" :options="Crm::assignable()" :value="$inquiry->agent" />
    <x-f.select label="Temperature" name="priority" :options="Crm::PRIOS" :value="$inquiry->priority" />
    <x-f.select label="Inquiry status" name="status" :options="Crm::ISTATUS" :value="$inquiry->status" />
  </div>

  <div class="sec">Requirement</div>
  <div class="g2">
    <x-f.select label="Service category" name="category" :options="array_keys($services)" :value="$inquiry->category" req />
    <x-f.select label="Service / sub-service" name="service" :options="$services[$cat] ?? []" :value="$inquiry->service" blank="Select…" req />
  </div>
  <x-f.area label="What does the customer need?" name="requirement" :value="$inquiry->requirement" rows="3" req />
  <div class="g3">
    <x-f.text label="Budget from" name="budget_from" type="number" step="any" min="0" :value="$inquiry->budget_from" />
    <x-f.text label="Budget to" name="budget_to" type="number" step="any" min="0" :value="$inquiry->budget_to" />
    <x-f.text label="Expected start date" name="start_date" type="date" :value="$inquiry->start_date?->format('Y-m-d')" />
  </div>
  @unless ($editing)
    <x-f.text label="Schedule a follow-up (optional)" name="next_fu" type="datetime-local" />
  @endunless
  <x-f.area label="Notes" name="notes" :value="$inquiry->notes" rows="2" />
  @if (Crm::customersOn())
    <p class="hint">Setting the status to “Won” converts the lead into a customer automatically.</p>
  @endif

  <div class="mf">
    <a class="btn" href="{{ $back }}">Cancel</a>
    <button class="btn pri">Save</button>
  </div>
</form>
@endsection
