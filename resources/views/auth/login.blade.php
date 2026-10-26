<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@php($co = \App\Support\Crm::setting('company')['name'] ?: 'CRM')
<title>Sign in · {{ $co }}</title>
@include('partials.favicon')
<link rel="stylesheet" href="{{ asset('css/crm.css') }}?v=4">
</head>
<body>
<div class="auth">
  <form class="card stack" method="POST" action="{{ route('login') }}">
    @csrf
    <div class="logo" style="padding-bottom:4px"><i>{{ mb_strtoupper(mb_substr($co, 0, 1)) }}</i><div>{{ $co }}</div></div>
    <div class="sub">Sign in to continue</div>
    <x-f.text label="Email" name="email" type="email" req autofocus />
    <x-f.text label="Password" name="password" type="password" req />
    <label class="sub" style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="remember" value="1"> Remember me</label>
    <button class="btn pri">Sign in</button>
  </form>
</div>
<script src="{{ asset('js/crm.js') }}?v=4"></script>
</body>
</html>
