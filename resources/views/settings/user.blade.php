@extends('layouts.app')
@section('title', 'Edit '.$user->name)
@section('nav', 'settings.index')

@section('content')
<div class="ph">
  <div>
    <div class="sub"><a class="lnk" href="{{ route('settings.index') }}">← Settings</a></div>
    <h1>Edit user</h1>
  </div>
</div>

<form class="card formcard" style="max-width:640px" method="POST" action="{{ route('users.update', $user) }}">
  @csrf @method('PUT')
  <div class="g2">
    <x-f.text label="Name" name="name" :value="$user->name" req />
    <x-f.text label="Email (used to log in)" name="email" type="email" :value="$user->email" req />
    <x-f.select label="Role" name="role" :options="\App\Models\User::ROLES" :value="$user->role" assoc />
    <x-f.text label="New password (leave blank to keep)" name="password" type="password" autocomplete="new-password" />
  </div>
  <label class="sub" style="display:flex;gap:6px;align-items:center">
    <input type="hidden" name="active" value="0">
    <input type="checkbox" name="active" value="1" @checked(old('active', $user->active))> Active – can log in
  </label>
  <p class="hint">Renaming a user also updates the “Agent” on their leads, inquiries and follow-ups. Deactivate instead of deleting so their history stays.</p>
  <div class="mf">
    <a class="btn" href="{{ route('settings.index') }}">Cancel</a>
    <button class="btn pri">Save</button>
  </div>
</form>
@endsection
