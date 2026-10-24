@extends('layouts.app')
@php
  use App\Support\Crm;
  $editing = $followup->exists;
  $cid = old('company_id', $followup->company_id);
  $inqOptions = collect($companies[$cid]['inquiries'] ?? [])->pluck('label', 'id')->all();
  $back = $followup->company_id ? route('companies.show', $followup->company_id) : route('followups.index');
@endphp
@section('title', $editing ? 'Follow-up '.$followup->code : 'New follow-up')
@section('nav', 'followups.index')
@if ($followup->company_id) @section('company_id', $followup->company_id) @endif

@section('content')
<div class="ph">
  <div>
    <div class="sub"><a class="lnk" href="{{ $back }}">← {{ $followup->company?->name ?? 'Follow-ups' }}</a></div>
    <h1>{{ $editing ? 'Follow-up '.$followup->code : 'New follow-up' }}</h1>
  </div>
  @if ($editing)
    <div class="btns">
      @if ($followup->status === 'Pending') @include('followups._done-btn', ['f' => $followup]) @endif
      <form class="inline" method="POST" action="{{ route('followups.destroy', $followup) }}" data-confirm="Delete this follow-up?">@csrf @method('DELETE')<button class="btn sm danger">Delete</button></form>
    </div>
  @endif
</div>

<form class="card formcard" style="max-width:760px" method="POST" action="{{ $editing ? route('followups.update', $followup) : route('followups.store') }}" data-companies='@json($companies)'>
  @csrf
  @if ($editing) @method('PUT') @endif

  <div class="g2">
    <x-f.select label="Lead / customer" name="company_id" :options="collect($companies)->map(fn ($c) => $c['label'])->all()" :value="$followup->company_id" blank="Select…" assoc req />
    <x-f.select label="Related inquiry" name="inquiry_id" :options="$inqOptions" :value="$followup->inquiry_id" blank="— none —" assoc />
    <x-f.text label="Date & time" name="due" type="datetime-local" :value="$followup->due?->format('Y-m-d\TH:i')" req />
    <x-f.select label="Method" name="method" :options="Crm::METHODS" :value="$followup->method" />
    <x-f.select label="Sales agent" name="agent" :options="Crm::assignable()" :value="$followup->agent" />
    <x-f.text label="Purpose" name="purpose" :value="$followup->purpose" />
  </div>
  @if ($editing)
    <div class="g2">
      <x-f.select label="Status" name="status" :options="Crm::FSTATUS" :value="$followup->status" />
      <span></span>
    </div>
    <x-f.area label="Outcome / notes" name="outcome" :value="$followup->outcome" rows="3" />
  @endif

  <div class="mf">
    <a class="btn" href="{{ $back }}">Cancel</a>
    <button class="btn pri">Save</button>
  </div>
</form>
@endsection
