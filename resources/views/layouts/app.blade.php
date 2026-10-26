<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') · {{ $crmSettings['company']['name'] }}</title>
@include('partials.favicon')
<link rel="stylesheet" href="{{ asset('css/crm.css') }}?v=4">
</head>
<body>
@php
  $nav = [
    ['dashboard', 'Dashboard', 0],
    ['leads.index', 'Leads', 0],
    ['pipeline', 'Pipeline', 0],
    ['inquiries.index', 'Inquiries', 0],
    ['followups.index', 'Follow-ups', $overdueCount],
  ];
  if (\App\Support\Crm::customersOn()) {
      $nav[] = ['customers.index', 'Customers', 0];
  }
  $nav[] = ['documents.index', 'Documents', 0];
  if ($isAdmin) {
      $nav[] = ['settings.index', 'Settings', 0];
  }
  $section = trim($__env->yieldContent('nav'));
@endphp
<div id="app">
  <aside id="side">
    <div class="logo"><i>{{ mb_strtoupper(mb_substr($crmSettings['company']['name'] ?: 'D', 0, 1)) }}</i><div>{{ $crmSettings['company']['name'] }}</div></div>
    <nav class="nav">
      @foreach ($nav as [$route, $label, $count])
        <a href="{{ route($route) }}" @class(['on' => $section === $route])>{{ $label }}@if ($count)<span class="cnt">{{ $count }}</span>@endif</a>
      @endforeach
    </nav>
  </aside>
  <div id="main">
    <header id="top">
      <div id="gsw"><input id="gs" type="search" placeholder="{{ \App\Support\Crm::customersOn() ? 'Search leads & customers…' : 'Search leads…' }}" autocomplete="off" data-url="{{ route('search') }}"><div id="gsr"></div></div>
      <div class="spacer"></div>
      <a class="me" href="{{ route('account') }}" title="My account" style="text-decoration:none">
        <span>{{ auth()->user()->name }}</span>
        <span class="b {{ $isAdmin ? 'brand' : 'mute' }}">{{ \App\Models\User::ROLES[auth()->user()->role] ?? '' }}</span>
      </a>
      <div id="newwrap"><button class="btn pri" data-act="newmenu" type="button">+ New</button>
        <div id="newmenu">
          @php($cid = trim($__env->yieldContent('company_id')))
          <a href="{{ route('leads.create') }}">Lead</a>
          <a href="{{ route('inquiries.create', array_filter(['company' => $cid])) }}">Inquiry</a>
          <a href="{{ route('followups.create', array_filter(['company' => $cid])) }}">Follow-up</a>
          <a href="{{ route('proposals.create', array_filter(['company' => $cid])) }}">Proposal</a>
          <a href="{{ route('invoices.create', array_filter(['company' => $cid])) }}">Invoice</a>
          <a href="{{ route('payments.create') }}">Payment</a>
          <a href="{{ route('documents.create', array_filter(['type' => 'EoI', 'company' => $cid])) }}">EoI</a>
          <a href="{{ route('documents.create', array_filter(['type' => 'RFQ', 'company' => $cid])) }}">RFQ</a>
        </div>
      </div>
      <form method="POST" action="{{ route('logout') }}" class="inline">@csrf<button class="btn sm">Log out</button></form>
    </header>
    <div id="banner"></div>
    <main id="content">
      @yield('content')
    </main>
  </div>
</div>

{{-- complete follow-up (shared by dashboard, follow-ups and company pages) --}}
<div class="ov" id="fuDone" hidden>
  <form class="md" method="POST" action="">
    @csrf
    <div class="mh"><h3>Complete follow-up</h3><button class="x" data-act="close" type="button" title="Close">×</button></div>
    <div class="mb">
      <div class="note" id="fuDoneInfo"></div>
      <x-f.area label="What happened? (saved to the remarks timeline)" name="outcome" rows="3" />
      <div class="sec">Next step</div>
      <div class="g2">
        <x-f.text label="Next follow-up (optional)" name="next_due" type="datetime-local" />
        <x-f.text label="Purpose of next follow-up" name="next_purpose" />
      </div>
    </div>
    <div class="mf"><button class="btn" data-act="close" type="button">Cancel</button><button class="btn pri">Mark done</button></div>
  </form>
</div>

@stack('modals')
<div id="toast"></div>
<div id="flash" data-msg="{{ session('ok') ?? ($errors->any() ? 'Please check the highlighted fields' : (session('err') ?? '')) }}" data-kind="{{ session('ok') ? '' : 'err' }}"></div>
<script src="{{ asset('js/crm.js') }}?v=4"></script>
</body>
</html>
