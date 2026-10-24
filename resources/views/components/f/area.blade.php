@props(['label', 'name', 'value' => null, 'rows' => 3, 'req' => false])
@php($key = rtrim(str_replace(['[', ']'], ['.', ''], $name), '.'))
<label class="f"><span>{{ $label }}{{ $req ? ' *' : '' }}</span>
  <textarea name="{{ $name }}" rows="{{ $rows }}" {{ $attributes->class(['bad' => $errors->has($key)]) }} @required($req)>{{ old($key, $value) }}</textarea>
  @error($key)<span class="err">{{ $message }}</span>@enderror
</label>
