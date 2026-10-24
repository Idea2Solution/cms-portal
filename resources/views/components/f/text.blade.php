@props(['label', 'name', 'value' => null, 'type' => 'text', 'req' => false])
@php($key = rtrim(str_replace(['[', ']'], ['.', ''], $name), '.'))
<label class="f"><span>{{ $label }}{{ $req ? ' *' : '' }}</span>
  @if ($type === 'password')
    {{-- password fields never echo a value back; the eye button toggles visibility --}}
    <span class="pw">
      <input name="{{ $name }}" type="password" {{ $attributes->class(['bad' => $errors->has($key)]) }} @required($req)>
      <button type="button" class="pw-eye" data-act="pw" title="Show password" aria-label="Show password">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/><line class="pw-slash" x1="3" y1="3" x2="21" y2="21"/></svg>
      </button>
    </span>
  @else
    <input name="{{ $name }}" type="{{ $type }}" value="{{ old($key, $value) }}" {{ $attributes->class(['bad' => $errors->has($key)]) }} @required($req)>
  @endif
  @error($key)<span class="err">{{ $message }}</span>@enderror
</label>
