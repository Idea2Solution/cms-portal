@extends('layouts.app')
@section('title', 'Customers')
@section('nav', 'customers.index')

@section('content')
<div class="ph">
  <div><h1>Customers</h1><div class="sub">Won business – every lead keeps its full history when converted</div></div>
</div>

<form class="filters" method="GET">
  <input type="search" name="q" placeholder="Search customers…" value="{{ request('q') }}">
</form>

@if ($customers->isEmpty())
  <div class="empty">No customers yet. A lead becomes a customer when an inquiry is won (or use “Convert to customer”).</div>
@else
  <div class="tw"><table>
    <thead><tr><th>No.</th><th>Company</th><th>Category</th><th>Contact</th><th>Phone</th><th>Agent</th><th>Open inquiries</th><th>Total inquiries</th><th>Customer since</th></tr></thead>
    <tbody>
      @foreach ($customers as $c)
        <tr data-act="go" data-href="{{ route('companies.show', $c) }}">
          <td><span class="mut nw">{{ $c->customer_no }}</span></td>
          <td><a class="lnk" href="{{ route('companies.show', $c) }}">{{ $c->name }}</a></td>
          <td>{{ $c->category ?: '—' }}</td>
          <td>{{ $c->contact ?: '—' }}</td>
          <td>{{ $c->phone ?: '—' }}</td>
          <td>{{ $c->agent ?: '—' }}</td>
          <td>{{ $c->open_inquiries_count }}</td>
          <td>{{ $c->inquiries_count }}</td>
          <td class="nw">{{ $c->converted_at?->format('j M Y') ?? '—' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
@endif
@include('partials.pager', ['p' => $customers])
@endsection
