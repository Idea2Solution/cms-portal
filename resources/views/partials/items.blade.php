{{-- Line items editor shared by proposals and invoices. $items: list of {service, desc, qty, price} --}}
@php
  $items = old('items', $items ?: []) ?: [['service' => '', 'desc' => '', 'qty' => 1, 'price' => '']];
  $allServices = collect(\App\Support\Crm::setting('services'))->flatten()->unique()->values();
@endphp
<div class="sec">Items</div>
@error('items')<div class="err" style="margin-bottom:6px">{{ $message }}</div>@enderror
<div class="tw"><table class="it">
  <thead><tr><th>Service</th><th>Description</th><th style="width:74px">Qty</th><th style="width:116px">Unit price</th><th class="r" style="width:100px">Amount</th><th style="width:34px"></th></tr></thead>
  <tbody id="its">
    @foreach (array_values($items) as $n => $i)
      <tr class="it-row">
        <td><input name="items[{{ $n }}][service]" class="i-s" list="dl-svc" value="{{ $i['service'] ?? '' }}" placeholder="Service"></td>
        <td><input name="items[{{ $n }}][desc]" class="i-d" value="{{ $i['desc'] ?? '' }}" placeholder="Description"></td>
        <td><input name="items[{{ $n }}][qty]" class="i-q" type="number" min="0" step="any" value="{{ $i['qty'] ?? 1 }}"></td>
        <td><input name="items[{{ $n }}][price]" class="i-p @error("items.$n.price") bad @enderror" type="number" min="0" step="any" value="{{ $i['price'] ?? '' }}"></td>
        <td class="amt r nw">0.00</td>
        <td><button class="x" type="button" data-act="it-del" title="Remove line">×</button></td>
      </tr>
    @endforeach
  </tbody>
</table></div>
<template id="it-tpl">
  <tr class="it-row">
    <td><input name="items[__i__][service]" class="i-s" list="dl-svc" placeholder="Service"></td>
    <td><input name="items[__i__][desc]" class="i-d" placeholder="Description"></td>
    <td><input name="items[__i__][qty]" class="i-q" type="number" min="0" step="any" value="1"></td>
    <td><input name="items[__i__][price]" class="i-p" type="number" min="0" step="any"></td>
    <td class="amt r nw">0.00</td>
    <td><button class="x" type="button" data-act="it-del" title="Remove line">×</button></td>
  </tr>
</template>
<button class="btn sm" type="button" data-act="it-add" style="margin-top:8px">+ Add line</button>
<datalist id="dl-svc">@foreach ($allServices as $s)<option value="{{ $s }}">@endforeach</datalist>
<div id="tot" @isset($paid) data-paid="{{ $paid }}" @endisset></div>
