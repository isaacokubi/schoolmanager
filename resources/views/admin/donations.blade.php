{{--
    PRESENTATION NOTE: donations template.
    This Blade view displays page content using data and state prepared by Laravel.
    The directives below control reusable layout sections, loops, and conditional content.
--}}

{{-- Inherit the shared layout and its common structure. --}}
@extends('layouts.admin')
{{-- Define the page content that the parent layout will render. --}}
@section('page_title','Support Pledges')
{{-- Define the page content that the parent layout will render. --}}
@section('admin_content')
<div class="admin-page-head"><div><h1>Support Pledges</h1><p>Track community support commitments and follow-up status.</p></div></div>
{{-- Show this content only when the condition is true. --}}
@if(session('success'))<div class="flash success">{{ session('success') }}</div>@endif
<div class="card"><div class="table-wrap"><table><thead><tr><th>Supporter</th><th>Purpose</th><th>Amount</th><th>Contact</th><th>Status</th><th>Action</th></tr></thead><tbody>
{{-- Iterate over the collection and render an alternative when it is empty. --}}
@forelse($donations as $d)<tr><td><strong>{{ $d->name }}</strong><small>{{ $d->email }}</small></td><td>{{ $d->purpose }}</td><td>{{ $d->amount ? 'KES '.number_format($d->amount,2) : 'Pledge' }}</td><td>{{ $d->phone ?: '—' }}</td><td>{{ ucfirst($d->status) }}</td><td><form method="POST" action="{{ route('admin.donations.status',$d->id) }}">@csrf @method('PATCH')<select name="status" onchange="this.form.submit()">@foreach(['pledged','contacted','received','cancelled'] as $s)<option value="{{ $s }}" @selected($d->status===$s)>{{ ucfirst($s) }}</option>@endforeach</select></form></td></tr>@empty<tr><td colspan="6">No support pledges yet.</td></tr>@endforelse
</tbody></table></div>{{ $donations->links() }}</div>
@endsection