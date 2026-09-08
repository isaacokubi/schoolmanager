@extends('layouts.app')
@section('title', ucfirst($user->role).' Dashboard | School Manager')
@section('body')
<div class="admin-shell">
<header class="admin-top"><div><strong>School Manager</strong><div style="font-size:13px;opacity:.75">{{ ucfirst($user->role) }} Portal</div></div><form method="post" action="{{ route('portal.logout') }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button class="btn secondary" type="submit">Sign out</button></form></header>
<main class="admin-main" style="max-width:1200px;margin:auto">
<p class="eyebrow">{{ ucfirst($user->role) }} dashboard</p><h1>Welcome, {{ $user->name }}</h1><p class="muted">Your personalised school portal.</p>
<div class="stat-grid" style="margin:25px 0"><div class="stat"><span class="muted">Account</span><strong>{{ ucfirst($user->role) }}</strong></div><div class="stat"><span class="muted">Linked pupils</span><strong>{{ $students->count() }}</strong></div><div class="stat"><span class="muted">Portal status</span><strong>Active</strong></div><div class="stat"><span class="muted">Email</span><strong style="font-size:18px">{{ $user->email }}</strong></div></div>
@if($profile && $profile->portal_type === 'pupil')
<section class="card"><h2>My academic profile</h2>@forelse($students as $student)<div class="card" style="margin-top:15px;background:#f8faff"><h3>{{ $student->name }}</h3><p><strong>Admission:</strong> {{ $student->admission_number }}</p><p><strong>Class:</strong> {{ $student->class_name ?: 'Not assigned' }}</p><p><strong>Fee balance:</strong> KES {{ number_format((float)$student->fee_balance, 2) }}</p></div>@empty<p class="muted">Your pupil record is not yet linked. Please contact the school office.</p>@endforelse</section>
@else
<section class="card"><h2>Linked pupils</h2><p class="muted">Monitor the learners connected to your portal account.</p>@forelse($students as $student)<div class="card" style="margin-top:15px;background:#f8faff"><h3>{{ $student->name }}</h3><p><strong>Admission:</strong> {{ $student->admission_number }} &nbsp; <strong>Class:</strong> {{ $student->class_name ?: 'Not assigned' }}</p><p><strong>Fee balance:</strong> KES {{ number_format((float)$student->fee_balance, 2) }}</p></div>@empty<div class="empty"><h3>No pupils linked yet</h3><p>Your account is active, but no pupil has been linked to it yet. Contact the school administrator to connect a pupil.</p></div>@endforelse</section>
@endif
</main></div>
@endsection
