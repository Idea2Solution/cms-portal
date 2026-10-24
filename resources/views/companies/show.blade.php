@extends('layouts.app')
@php
  use App\Support\Crm;
  $c = $company;
  $isC = $c->isCustomer();
  $next = $c->nextFollowup();
  $wa = preg_replace('/\D/', '', $c->whatsapp ?: $c->phone ?: '');
  $url = fn ($u) => preg_match('/^https?:/i', $u) ? $u : 'https://'.$u;
@endphp
@section('title', $c->name)
@section('nav', $isC ? 'customers.index' : 'leads.index')
@section('company_id', $c->id)

@section('content')
<div class="ph">
  <div>
    <div class="sub"><a class="lnk" href="{{ route($isC ? 'customers.index' : 'leads.index') }}">← {{ $isC ? 'Customers' : 'Leads' }}</a></div>
    <h1>{{ $c->name }}</h1>
    <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap;align-items:center">
      <x-badge :text="$isC ? 'Customer '.$c->customer_no : 'Lead'" tone="brand" />
      <x-prio :p="$c->priority" />
      <x-badge :text="$c->status" />
      <x-badge :text="'Stage: '.$c->stage()" tone="info" />
      <span class="mut">{{ $c->code }} · Agent: {{ $c->agent ?: '—' }}</span>
    </div>
  </div>
  <div class="btns">
    <a class="btn sm" href="{{ route('companies.edit', $c) }}">Edit</a>
    <a class="btn sm" href="{{ route('inquiries.create', ['company' => $c->id]) }}">+ Inquiry</a>
    <a class="btn sm" href="{{ route('followups.create', ['company' => $c->id]) }}">+ Follow-up</a>
    @unless ($isC || ! Crm::customersOn())
      <form class="inline" method="POST" action="{{ route('companies.convert', $c) }}" data-confirm="Convert {{ $c->name }} to a customer? All inquiries and history stay on the same record.">@csrf<button class="btn sm pri">Convert to customer</button></form>
    @endunless
    <form class="inline" method="POST" action="{{ route('companies.archive', $c) }}" @if ($c->status !== 'Archived') data-confirm="Archive {{ $c->name }}? It is hidden from active lists but stays searchable." @endif>@csrf<button class="btn sm danger">{{ $c->status === 'Archived' ? 'Restore' : 'Archive' }}</button></form>
  </div>
</div>

<div class="grid21">
  <div class="stack">
    <div class="card"><h3>Contact &amp; lead details</h3>
      <dl class="kv">
        <dt>Contact</dt><dd>{{ $c->contact ?: '—' }}@if ($c->designation) <span class="mut">({{ $c->designation }})</span>@endif</dd>
        <dt>Phone</dt><dd>{{ collect([$c->phone, $c->phone2])->filter()->join(' / ') ?: '—' }}</dd>
        <dt>WhatsApp</dt><dd>@if ($c->whatsapp)<a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">{{ $c->whatsapp }}</a>@else — @endif</dd>
        <dt>Email</dt><dd>@if ($c->email)<a href="mailto:{{ $c->email }}">{{ $c->email }}</a>@else — @endif</dd>
        <dt>Website</dt><dd>@if ($c->website)<a href="{{ $url($c->website) }}" target="_blank" rel="noopener">{{ $c->website }}</a>@else — @endif</dd>
        <dt>Social</dt><dd>
          @forelse ($c->socials ?? [] as $s)
            <a href="{{ $url($s['url']) }}" target="_blank" rel="noopener">{{ $s['network'] }}</a>@if (! $loop->last) · @endif
          @empty — @endforelse
        </dd>
        <dt>Location</dt><dd>{{ collect([$c->city, $c->country])->filter()->join(', ') ?: '—' }}</dd>
        <dt>Category</dt><dd>{{ $c->category ?: '—' }}</dd>
        <dt>Source</dt><dd>{{ $c->source ?: '—' }}{{ $c->source_url ? ' · '.$c->source_url : '' }}</dd>
        <dt>Last contacted</dt><dd>{{ $c->last_contacted?->format('j M Y') ?? '—' }}</dd>
        <dt>Next follow-up</dt><dd>{{ $next?->due->format('j M Y, H:i') ?? '—' }}</dd>
        @if ($c->description)<dt>About</dt><dd>{{ $c->description }}</dd>@endif
        @if ($c->notes)<dt>Notes</dt><dd>{{ $c->notes }}</dd>@endif
      </dl>
    </div>

    <div class="card"><h3>Sales remarks &amp; timeline</h3>
      <form class="remark" method="POST" action="{{ route('companies.remarks.store', $c) }}">
        @csrf
        <select name="kind" style="border:1px solid #DDD6CF;border-radius:8px;padding:7px 10px;font:inherit">
          @foreach (Crm::REMARK_KINDS as $k)<option @selected($k === 'Note')>{{ $k }}</option>@endforeach
        </select>
        <span class="mut" style="align-self:center;font-size:12.5px">Calls, WhatsApps, emails and meetings mark the lead as contacted.</span>
        <button class="btn pri sm">Add remark</button>
        <textarea name="text" rows="2" required placeholder="What was discussed? Customer feedback, objections, next step…" style="border:1px solid #DDD6CF;border-radius:8px;padding:8px 10px;font:inherit"></textarea>
      </form>
      <div class="tl">
        @forelse ($c->remarks as $r)
          <div @class(['it', 'sys' => $r->kind === 'System'])>
            <small>{{ $r->created_at->format('j M Y, H:i') }} · {{ $r->kind }} · {{ $r->by }}</small>
            <div>{{ $r->text }}</div>
          </div>
        @empty
          <div class="mut" style="padding:6px 0">No remarks yet.</div>
        @endforelse
      </div>
    </div>
  </div>

  <div class="stack">
    <div class="card"><h3>Inquiries <a class="btn sm" href="{{ route('inquiries.create', ['company' => $c->id]) }}">+ Add</a></h3>
      @forelse ($c->inquiries as $i)
        <div class="li" data-act="go" data-href="{{ route('inquiries.edit', $i) }}" style="cursor:pointer">
          <div><b>{{ $i->service }}</b> <span class="mut">{{ $i->code }}</span><small>{{ Str::limit($i->requirement, 60) }}</small></div>
          <x-badge :text="$i->status" />
        </div>
      @empty
        <div class="mut" style="padding:6px 0">No inquiries yet.</div>
      @endforelse
    </div>

    <div class="card"><h3>Follow-ups <a class="btn sm" href="{{ route('followups.create', ['company' => $c->id]) }}">+ Add</a></h3>
      @forelse ($c->followups->take(8) as $f)
        <div class="li">
          <div><a class="lnk" href="{{ route('followups.edit', $f) }}" style="font-weight:500">{{ $f->due->format('j M Y, H:i') }}</a>
            <small>{{ $f->purpose }} · {{ $f->method }}{{ $f->outcome ? ' — '.Str::limit($f->outcome, 50) : '' }}</small></div>
          <div class="nw">
            @if ($f->status === 'Pending')
              @if ($f->isOverdue())<x-badge text="Overdue" />@endif
              @include('followups._done-btn', ['f' => $f->setRelation('company', $c)])
            @else
              <x-badge :text="$f->status" />
            @endif
          </div>
        </div>
      @empty
        <div class="mut" style="padding:6px 0">No follow-ups scheduled.</div>
      @endforelse
    </div>

    @if (Crm::isAdmin())
    <div class="card"><h3>Danger zone</h3>
      <form method="POST" action="{{ route('companies.destroy', $c) }}" data-confirm="Permanently delete {{ $c->name }} with all its inquiries, follow-ups and remarks? This cannot be undone.">
        @csrf @method('DELETE')
        <p class="hint" style="margin-top:0">Prefer “Archive” – it keeps the history. Delete removes everything.</p>
        <button class="btn sm danger">Delete {{ $isC ? 'customer' : 'lead' }}</button>
      </form>
    </div>
    @endif
  </div>
</div>
@endsection
