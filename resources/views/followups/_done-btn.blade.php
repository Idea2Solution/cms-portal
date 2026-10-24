<button class="btn sm" type="button" data-act="fu-done" data-url="{{ route('followups.complete', $f) }}"
  data-info="{{ ($f->company->name ?? '') . ' · ' . ($f->purpose ?: $f->method) . ' · ' . $f->due->format('j M Y, H:i') }}">Done</button>
