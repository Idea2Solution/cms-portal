@extends('layouts.app')
@php
  use App\Support\Crm;
  $editing = $company->exists;
  $noun = $company->isCustomer() ? 'customer' : 'lead';
  $socials = old('socials', $company->socials ?: []);
@endphp
@section('title', $editing ? 'Edit '.$company->name : 'New lead')
@section('nav', $company->isCustomer() ? 'customers.index' : 'leads.index')

@section('content')
<div class="ph">
  <div>
    <div class="sub"><a class="lnk" href="{{ $editing ? route('companies.show', $company) : route('leads.index') }}">← {{ $editing ? $company->name : 'Leads' }}</a></div>
    <h1>{{ $editing ? 'Edit '.$noun : 'New lead' }}</h1>
  </div>
</div>

<form class="card formcard" method="POST" action="{{ $editing ? route('companies.update', $company) : route('leads.store') }}">
  @csrf
  @if ($editing) @method('PUT') @endif

  <div class="sec">Company</div>
  <div class="g2">
    <x-f.text label="Company name" name="name" :value="$company->name" req />
    <x-f.text label="Business category" name="category" :value="$company->category" list="dl-cats" req />
    <x-f.text label="Website" name="website" :value="$company->website" />
    <x-f.text label="Country" name="country" :value="$company->country" />
    <x-f.text label="City" name="city" :value="$company->city" />
    <x-f.text label="Address" name="address" :value="$company->address" />
  </div>
  <x-f.area label="Business description" name="description" :value="$company->description" rows="2" />

  <div class="sec">Contact</div>
  <div class="g2">
    <x-f.text label="Contact person" name="contact" :value="$company->contact" req />
    <x-f.text label="Designation" name="designation" :value="$company->designation" />
    <x-f.text label="Primary phone" name="phone" :value="$company->phone" req />
    <x-f.text label="Secondary phone" name="phone2" :value="$company->phone2" />
    <x-f.text label="Email" name="email" type="email" :value="$company->email" />
    <x-f.text label="WhatsApp" name="whatsapp" :value="$company->whatsapp" />
    <x-f.select label="Preferred contact method" name="preferred" :options="Crm::METHODS" :value="$company->preferred" blank="—" />
  </div>

  <div class="sec">Social media</div>
  <div id="soc">
    @foreach ($socials as $i => $s)
      <div class="soc-row">
        <select name="socials[{{ $i }}][network]">@foreach (Crm::SOCIALS as $n)<option @selected(($s['network'] ?? '') === $n)>{{ $n }}</option>@endforeach</select>
        <input name="socials[{{ $i }}][url]" placeholder="https://…" value="{{ $s['url'] ?? '' }}">
        <button class="x" type="button" data-act="soc-del" title="Remove">×</button>
      </div>
    @endforeach
  </div>
  <template id="soc-tpl">
    <div class="soc-row">
      <select name="socials[__i__][network]">@foreach (Crm::SOCIALS as $n)<option>{{ $n }}</option>@endforeach</select>
      <input name="socials[__i__][url]" placeholder="https://…">
      <button class="x" type="button" data-act="soc-del" title="Remove">×</button>
    </div>
  </template>
  <button class="btn sm" type="button" data-act="soc-add">+ Add social media</button>

  <div class="sec">Lead</div>
  <div class="g3">
    <x-f.select label="Lead source" name="source" :options="Crm::SOURCES" :value="$company->source" blank="Select…" req />
    <x-f.text label="Source URL / campaign" name="source_url" :value="$company->source_url" />
    <x-f.select label="Assigned agent" name="agent" :options="Crm::assignable()" :value="$company->agent" />
    <x-f.select label="Temperature" name="priority" :options="Crm::PRIOS" :value="$company->priority" />
    <x-f.select label="Lead status" name="status" :options="Crm::LSTATUS" :value="$company->status" />
    <x-f.text label="Schedule a follow-up (optional)" name="next_fu" type="datetime-local" />
  </div>
  <x-f.area label="Notes" name="notes" :value="$company->notes" rows="2" />

  <datalist id="dl-cats">@foreach (Crm::setting('categories') as $c)<option value="{{ $c }}">@endforeach</datalist>

  <div class="mf">
    <a class="btn" href="{{ $editing ? route('companies.show', $company) : route('leads.index') }}">Cancel</a>
    <button class="btn pri">Save</button>
  </div>
</form>
@endsection
