{{-- Tab icon matching the sidebar logo: brand gradient square with the company's first letter --}}
@php
  $letter = e(mb_strtoupper(mb_substr(\App\Support\Crm::setting('company')['name'] ?: 'D', 0, 1)));
  $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#F26B1D"/><stop offset="1" stop-color="#C8400B"/></linearGradient></defs>'
       .'<rect width="64" height="64" rx="16" fill="url(#g)"/>'
       .'<text x="32" y="45" text-anchor="middle" font-family="Segoe UI,Arial,sans-serif" font-size="38" font-weight="800" fill="#fff">'.$letter.'</text></svg>';
@endphp
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,{{ rawurlencode($svg) }}">
