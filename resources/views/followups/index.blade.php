@extends('layouts.app')
@section('title', 'Follow-ups')
@section('nav', 'followups.index')

@section('content')
<div class="ph">
  <div><h1>Follow-ups</h1><div class="sub">Never let a lead go quiet</div></div>
  <div><a class="btn pri" href="{{ route('followups.create') }}">+ New follow-up</a></div>
</div>

<form class="filters" method="GET">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div class="tabs" style="margin:0">
    @foreach (\App\Http\Controllers\FollowupController::TABS as $k => $l)
      <a href="{{ route('followups.index', array_filter(['tab' => $k, 'agent' => request('agent')])) }}" @class(['tab', 'on' => $tab === $k]) style="text-decoration:none">{{ $l }}<i>{{ $counts[$k] }}</i></a>
    @endforeach
  </div>
  <span class="spacer"></span>
  @if ($isAdmin)
  <x-filter-select name="agent" :options="\App\Support\Crm::agents()" label="All agents" />
  @endif
</form>

@if ($followups->isEmpty())
  <div class="empty">Nothing in this list.</div>
@else
  <div class="tw"><table>
    <thead><tr><th>Due</th><th>Company</th><th>Purpose</th><th>Method</th><th>Agent</th><th>Inquiry</th><th>Status</th><th>Outcome</th><th></th></tr></thead>
    <tbody>
      @foreach ($followups as $f)
        <tr>
          <td><span class="nw" @if ($f->isOverdue()) style="color:var(--bad);font-weight:600" @endif>{{ $f->due->format('j M Y, H:i') }}</span></td>
          <td><a class="lnk" href="{{ route('companies.show', $f->company_id) }}">{{ $f->company->name }}</a></td>
          <td>{{ $f->purpose ?: '—' }}</td>
          <td>{{ $f->method }}</td>
          <td>{{ $f->agent ?: '—' }}</td>
          <td>@if ($f->inquiry)<a class="lnk" href="{{ route('inquiries.edit', $f->inquiry_id) }}">{{ $f->inquiry->code }}</a>@else — @endif</td>
          <td><x-badge :text="$f->status" /></td>
          <td>{{ Str::limit($f->outcome, 50) }}</td>
          <td class="nw">
            @if ($f->status === 'Pending')
              @include('followups._done-btn', ['f' => $f])
              <a class="btn sm" href="{{ route('followups.edit', $f) }}">Edit</a>
            @else
              <a class="btn sm" href="{{ route('followups.edit', $f) }}">View</a>
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
@endif
@include('partials.pager', ['p' => $followups])
@endsection
