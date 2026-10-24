@props(['name', 'options', 'label'])
<select name="{{ $name }}">
  <option value="">{{ $label }}</option>
  @foreach ($options as $o)
    <option value="{{ $o }}" @selected(request($name) === $o)>{{ $o }}</option>
  @endforeach
</select>
