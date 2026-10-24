@props(['text', 'tone' => null])
<span class="b {{ $tone ?? \App\Support\Crm::tone($text) }}">{{ $text }}</span>
