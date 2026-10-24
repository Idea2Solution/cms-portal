@extends('layouts.app')
@section('title', 'My account')

@section('content')
<div class="ph">
  <div><h1>My account</h1><div class="sub">{{ $user->name }} · {{ $user->email }} · {{ \App\Models\User::ROLES[$user->role] ?? $user->role }}</div></div>
</div>

<form class="card formcard" style="max-width:520px" method="POST" action="{{ route('account.password') }}">
  @csrf @method('PUT')
  <h3>Change password</h3>
  <div class="stack">
    <x-f.text label="Current password" name="current_password" type="password" req autocomplete="current-password" />
    <x-f.text label="New password (min. 8 characters)" name="password" type="password" req autocomplete="new-password" />
    <x-f.text label="Confirm new password" name="password_confirmation" type="password" req autocomplete="new-password" />
  </div>
  <div class="mf"><button class="btn pri">Change password</button></div>
</form>
@endsection
