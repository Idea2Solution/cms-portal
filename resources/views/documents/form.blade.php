@extends('layouts.app')
@php
  use App\Support\Crm;
  use App\Support\CompanyOptions;
  $d = $document;
  $editing = $d->exists;
  $cid = old('company_id', $d->company_id);
  $inqOptions = collect($companies[$cid]['inquiries'] ?? [])->pluck('label', 'id')->all();
  $back = $d->company_id ? route('companies.show', $d->company_id) : route('documents.index', ['type' => $d->type]);
@endphp
@section('title', $editing ? $d->type.' '.$d->code : 'New '.$d->type)
@section('nav', 'documents.index')
@if ($d->company_id) @section('company_id', $d->company_id) @endif

@section('content')
<div class="ph">
  <div>
    <div class="sub"><a class="lnk" href="{{ $back }}">← {{ $d->company?->name ?? 'Documents' }}</a></div>
    <h1>{{ $editing ? $d->type.' '.$d->code : 'New '.$d->type }}</h1>
  </div>
  @if ($editing)
    <div class="btns">
      <form class="inline" method="POST" action="{{ route('documents.destroy', $d) }}" data-confirm="Delete {{ $d->code }} and its attached files?">@csrf @method('DELETE')<button class="btn sm danger">Delete</button></form>
    </div>
  @endif
</div>

<form class="card formcard" style="max-width:860px" method="POST" enctype="multipart/form-data" action="{{ $editing ? route('documents.update', $d) : route('documents.store') }}" data-companies='@json($companies)'>
  @csrf
  @if ($editing) @method('PUT') @else <input type="hidden" name="type" value="{{ $d->type }}"> @endif

  <div class="note">{{ $d->type === 'EoI' ? 'Expression of Interest — record it here and attach the file.' : 'Request for Quotation — record it here, then create a proposal from the inquiry.' }}</div>
  <div class="g3">
    <x-f.select label="Lead / customer" name="company_id" :options="CompanyOptions::labels($companies)" :value="$d->company_id" blank="Select…" assoc req />
    <x-f.select label="Related inquiry" name="inquiry_id" :options="$inqOptions" :value="$d->inquiry_id" blank="— none —" assoc />
    <x-f.select label="Direction" name="direction" :options="['Received', 'Sent']" :value="$d->direction" />
    <x-f.text label="Date" name="date" type="date" :value="$d->date?->format('Y-m-d')" />
    <x-f.text label="Client / external reference no." name="ref" :value="$d->ref" />
    <x-f.select label="Status" name="status" :options="Crm::DSTATUS" :value="$d->status" />
  </div>
  <x-f.area label="Summary / notes" name="notes" :value="$d->notes" rows="3" />

  <div class="sec">Attachments</div>
  @forelse ($d->files ?? [] as $n => $f)
    <div class="li">
      <a href="{{ route('documents.download', [$d, $n]) }}">{{ $f['name'] }}</a>
      <label class="mut" style="display:flex;gap:5px;align-items:center;font-size:12.5px"><input type="checkbox" name="remove_files[]" value="{{ $f['path'] }}"> Remove</label>
    </div>
  @empty
    <div class="mut">No files attached.</div>
  @endforelse
  <div class="inl"><input type="file" name="files[]" multiple accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt"></div>
  @error('files.*')<span class="err">{{ $message }}</span>@enderror
  <p class="hint">PDF, images, Word, Excel or text files – up to 20 MB each.</p>

  <div class="mf">
    <a class="btn" href="{{ $back }}">Cancel</a>
    <button class="btn pri">Save</button>
  </div>
</form>
@endsection
