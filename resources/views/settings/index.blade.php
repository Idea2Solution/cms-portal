@extends('layouts.app')
@section('title', 'Settings')
@section('nav', 'settings.index')


@section('content')
{{-- chip list with remove buttons + an "add" box for one editable list --}}
@php
  $co = $s['company'];
  $chips = function (array $items, string $k, string $c = '') {
      $html = '<div class="chips">';
      foreach ($items as $v) {
          $html .= '<span class="chip">'.e($v)
              .'<form class="inline" method="POST" action="'.route('settings.list.remove').'">'.csrf_field().method_field('DELETE')
              .'<input type="hidden" name="k" value="'.e($k).'"><input type="hidden" name="c" value="'.e($c).'"><input type="hidden" name="value" value="'.e($v).'">'
              .'<button class="x" title="Remove">×</button></form></span>';
      }
      return $html.'</div>';
  };
  $add = fn (string $k, string $ph, string $c = '') => '<form class="inl" method="POST" action="'.route('settings.list.add').'">'.csrf_field()
      .'<input type="hidden" name="k" value="'.e($k).'"><input type="hidden" name="c" value="'.e($c).'">'
      .'<input name="value" placeholder="'.e($ph).'" required><button class="btn sm">Add</button></form>';
@endphp
<div class="ph">
  <div><h1>Settings</h1><div class="sub">Team, service catalogue, defaults and company details</div></div>
</div>

<div class="grid2">
  <div class="stack">
    <div class="card"><h3>Sales team &amp; logins</h3>
      @foreach ($users as $u)
        <div class="li">
          <div><b @if (! $u->active) class="mut" @endif>{{ $u->name }}</b>
            <small>{{ $u->email }}</small></div>
          <div class="btns">
            <x-badge :text="\App\Models\User::ROLES[$u->role] ?? $u->role" :tone="$u->isAdmin() ? 'brand' : 'info'" />
            @unless ($u->active)<x-badge text="Inactive" tone="mute" />@endunless
            <a class="btn sm" href="{{ route('users.edit', $u) }}">Edit</a>
          </div>
        </div>
      @endforeach

      <div class="sec" style="margin-top:14px">Add a salesperson</div>
      <form method="POST" action="{{ route('users.store') }}">
        @csrf
        <div class="g2">
          <x-f.text label="Name" name="name" req />
          <x-f.text label="Email (used to log in)" name="email" type="email" req />
          <x-f.text label="Password (min. 8 characters)" name="password" type="password" req autocomplete="new-password" />
          <x-f.select label="Role" name="role" :options="\App\Models\User::ROLES" value="agent" assoc />
        </div>
        <button class="btn pri">Add user</button>
      </form>
      <p class="hint">Sales agents only see the leads, inquiries and follow-ups assigned to them. Admins see everything and manage settings.</p>
    </div>

    <div class="card"><h3>Business categories</h3>
      {!! $chips($s['categories'], 'categories') !!}
      {!! $add('categories', 'Add a category') !!}
    </div>

    <div class="card"><h3>Defaults &amp; company details</h3>
      <form method="POST" action="{{ route('settings.update') }}">
        @csrf @method('PUT')
        <div class="g3">
          <x-f.select label="Currency" name="currency" :options="\App\Support\Crm::CURS" :value="$s['currency']" />
          <x-f.text label="Default tax %" name="taxPct" type="number" step="any" :value="$s['taxPct']" />
          <span></span>
        </div>
        <x-f.area label="Default payment terms / notes on documents" name="terms" :value="$s['terms']" rows="2" />
        <div class="g2" style="margin-top:12px">
          <x-f.text label="Company name" name="company[name]" :value="$co['name']" req />
          <x-f.text label="Tagline" name="company[tagline]" :value="$co['tagline']" />
          <x-f.text label="Email" name="company[email]" :value="$co['email']" />
          <x-f.text label="Phone" name="company[phone]" :value="$co['phone']" />
          <x-f.text label="Website" name="company[website]" :value="$co['website']" />
          <x-f.text label="Address" name="company[address]" :value="$co['address']" />
        </div>
        <button class="btn pri">Save settings</button>
      </form>
    </div>
  </div>

  <div class="stack">
    <div class="card"><h3>Service catalogue</h3>
      @foreach ($s['services'] as $cat => $list)
        <div style="margin-bottom:12px">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <b>{{ $cat }}</b>
            <form class="inline" method="POST" action="{{ route('settings.list.remove') }}" data-confirm="Remove the service category “{{ $cat }}”?">
              @csrf @method('DELETE')
              <input type="hidden" name="k" value="cat"><input type="hidden" name="value" value="{{ $cat }}">
              <button class="btn sm danger" title="Remove category">Remove</button>
            </form>
          </div>
          {!! $chips($list, 'svc', $cat) !!}
          {!! $add('svc', 'Add sub-service', $cat) !!}
        </div>
      @endforeach
      {!! $add('cat', 'New service category') !!}
    </div>

    <div class="card"><h3>Export</h3>
      <p class="hint" style="margin-top:0">Download your records as CSV (opens in Excel / Google Sheets).</p>
      <div class="btns">
        <a class="btn sm" href="{{ route('settings.export', 'leads') }}">Leads</a>
        @if (\App\Support\Crm::customersOn())
          <a class="btn sm" href="{{ route('settings.export', 'customers') }}">Customers</a>
        @endif
        <a class="btn sm" href="{{ route('settings.export', 'inquiries') }}">Inquiries</a>
        <a class="btn sm" href="{{ route('settings.export', 'followups') }}">Follow-ups</a>
      </div>
    </div>
  </div>
</div>
@endsection
