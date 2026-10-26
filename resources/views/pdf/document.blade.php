@php
  use App\Support\Crm;
  $m = fn ($n) => $currency.' '.number_format((float) $n, 2);
  $c = $rec->company;
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $title }} {{ $rec->code }}</title>
<style>
  @page { margin: 16mm 16mm 22mm; }
  * { font-family: "DejaVu Sans", sans-serif; }
  body { font-size: 9.5pt; color: #1B2A41; margin: 0; }
  .brand { color: #F26B1D; }
  .mut { color: #6B7280; }
  table { width: 100%; border-collapse: collapse; }
  .head td { vertical-align: top; }
  .co { font-size: 20pt; font-weight: bold; color: #F26B1D; }
  .ttl { font-size: 16pt; font-weight: bold; text-align: right; }
  .rule { border-top: 2px solid #F26B1D; margin: 10px 0 14px; }
  .lbl { font-size: 7.5pt; font-weight: bold; color: #6B7280; letter-spacing: .05em; }
  .meta td { padding: 2px 0; font-size: 8.5pt; }
  .meta td.v { text-align: right; font-weight: bold; color: #1B2A41; font-size: 9pt; }
  .items { margin-top: 16px; }
  .items th { background: #F26B1D; color: #fff; font-size: 8pt; text-align: left; padding: 6px 6px; }
  .items td { padding: 6px; border-bottom: 1px solid #E8E2DC; vertical-align: top; }
  .r { text-align: right; }
  .tot { width: 46%; margin-left: 54%; margin-top: 10px; }
  .tot td { padding: 3px 4px; }
  .tot .gt td { background: #FFF1E6; font-weight: bold; font-size: 10.5pt; padding: 6px 4px; }
  .box { background: #FFF1E6; padding: 12px 14px; margin: 6px 0 16px; }
  .big { font-size: 20pt; font-weight: bold; color: #F26B1D; }
  .rows td { padding: 6px 0; border-bottom: 1px solid #E8E2DC; }
  .notes { margin-top: 18px; white-space: pre-line; }
  .foot { position: fixed; bottom: -12mm; left: 0; right: 0; border-top: 1px solid #E8E2DC; padding-top: 4px; text-align: center; font-size: 7pt; color: #6B7280; }
</style>
</head>
<body>
<div class="foot">{{ collect([$co['name'], $co['address'], $co['email'], $co['phone'], $co['website']])->filter()->join('  ·  ') }}</div>

<table class="head"><tr>
  <td><div class="co">{{ $co['name'] }}</div>@if ($co['tagline'])<div class="mut" style="font-size:8pt">{{ $co['tagline'] }}</div>@endif</td>
  <td><div class="ttl">{{ $title }}</div><div class="mut" style="text-align:right">{{ $rec->code }}</div></td>
</tr></table>
<div class="rule"></div>

<table class="head"><tr>
  <td style="width:55%">
    <div class="lbl">{{ $to }}</div>
    <div style="font-size:11pt;font-weight:bold;margin:3px 0">{{ $c->name }}</div>
    @foreach (array_filter([$c->contact, $c->address, collect([$c->city, $c->country])->filter()->join(', '), $c->email, $c->phone]) as $line)
      <div class="mut">{{ $line }}</div>
    @endforeach
  </td>
  <td>
    <table class="meta">
      @foreach ($meta as $k => $v)
        <tr><td class="mut">{{ $k }}</td><td class="v">{{ $v ?: '—' }}</td></tr>
      @endforeach
    </table>
  </td>
</tr></table>

@if ($kind === 'receipt')
  @php $inv = $rec->invoice; @endphp
  <div class="box" style="margin-top:18px">
    <div class="lbl">AMOUNT RECEIVED</div>
    <div class="big">{{ $m($rec->amount) }}</div>
  </div>
  <table class="rows">
    <tr><td class="mut">Invoice total</td><td class="r">{{ $m($inv->total) }}</td></tr>
    <tr><td class="mut">Paid to date (incl. this payment)</td><td class="r">{{ $m($inv->paid()) }}</td></tr>
    <tr><td class="mut">Balance due</td><td class="r"><b>{{ $m($inv->balance()) }}</b></td></tr>
  </table>
  @if ($rec->notes)
    <div class="notes"><div class="lbl">NOTES</div>{{ $rec->notes }}</div>
  @endif
  <p class="mut" style="margin-top:22px">Thank you for your payment.</p>
@else
  <table class="items">
    <thead><tr><th style="width:18px">#</th><th>DESCRIPTION</th><th class="r" style="width:50px">QTY</th><th class="r" style="width:80px">UNIT PRICE</th><th class="r" style="width:90px">AMOUNT</th></tr></thead>
    <tbody>
      @foreach ($rec->items as $n => $it)
        <tr>
          <td class="mut">{{ $n + 1 }}</td>
          <td><b>{{ $it['service'] }}</b>@if ($it['desc'])<div class="mut" style="font-size:8.5pt">{{ $it['desc'] }}</div>@endif</td>
          <td class="r">{{ rtrim(rtrim(number_format((float) $it['qty'], 2), '0'), '.') }}</td>
          <td class="r">{{ number_format((float) $it['price'], 2) }}</td>
          <td class="r">{{ number_format((float) $it['qty'] * (float) $it['price'], 2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table class="tot">
    <tr><td class="mut">Subtotal</td><td class="r">{{ $m($rec->subtotal) }}</td></tr>
    @if ((float) $rec->discount)<tr><td class="mut">Discount</td><td class="r">- {{ $m($rec->discount) }}</td></tr>@endif
    @if ((float) $rec->tax)<tr><td class="mut">Tax ({{ (float) $rec->tax_pct }}%)</td><td class="r">{{ $m($rec->tax) }}</td></tr>@endif
    <tr class="gt"><td>TOTAL</td><td class="r">{{ $m($rec->total) }}</td></tr>
    @if ($kind === 'invoice')
      <tr><td class="mut">Paid</td><td class="r">{{ $m($rec->paid()) }}</td></tr>
      <tr><td><b>Balance due</b></td><td class="r brand"><b>{{ $m($rec->balance()) }}</b></td></tr>
    @endif
  </table>

  @if ($rec->terms)
    <div class="notes"><div class="lbl">TERMS &amp; NOTES</div>{{ $rec->terms }}</div>
  @endif
@endif
</body>
</html>
