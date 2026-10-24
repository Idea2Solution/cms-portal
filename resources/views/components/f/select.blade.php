{{-- options: a plain list ['A','B'] or a map [value => label] when assoc is true --}}
@props(['label', 'name', 'options' => [], 'value' => null, 'blank' => null, 'req' => false, 'assoc' => false])
@php($key = rtrim(str_replace(['[', ']'], ['.', ''], $name), '.'))
@php($current = (string) old($key, $value))
<label class="f"><span>{{ $label }}{{ $req ? ' *' : '' }}</span>
  <select name="{{ $name }}" {{ $attributes->class(['bad' => $errors->has($key)]) }} @required($req)>
    @if ($blank !== null)<option value="">{{ $blank }}</option>@endif
    @foreach ($options as $k => $v)
      @php($val = $assoc ? (string) $k : (string) $v)
      <option value="{{ $val }}" @selected($val === $current)>{{ $v }}</option>
    @endforeach
  </select>
  @error($key)<span class="err">{{ $message }}</span>@enderror
</label>
