@extends('layouts.admin')
@section('title','School Management Hub')
@section('page_title','School Management Hub')
@section('admin_content')
<div class="admin-page-head"><div><p class="eyebrow">Angels Home feature layer</p><h1>School Management Hub</h1><p class="muted">Central administration for learning, communications, timetable, library, records and school operations.</p></div></div>
@if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
<div class="feature-tabs">@foreach($features as $key=>$label)<a class="{{ $feature===$key?'active':'' }}" href="{{ route('admin.features',['feature'=>$key]) }}">{{ $label }}</a>@endforeach</div>
<div class="stat-grid"><div class="stat"><span>Total</span><strong>{{ $stats['total'] }}</strong></div><div class="stat"><span>Published</span><strong>{{ $stats['published'] }}</strong></div><div class="stat"><span>Active</span><strong>{{ $stats['active'] }}</strong></div><div class="stat"><span>Feature</span><strong style="font-size:20px">{{ $features[$feature] }}</strong></div></div>
<div class="feature-grid">
<section class="card"><h2>Create / publish</h2>
<form method="POST" action="{{ route('admin.features.store') }}">@csrf
<div class="form-grid">
<div class="field"><label>Feature</label><select name="feature">@foreach($features as $key=>$label)<option value="{{ $key }}" {{ $feature===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label>Title</label><input name="title" required maxlength="220" placeholder="Lesson, book, announcement, timetable entry..."></div>
<div class="field"><label>Status</label><select name="status"><option value="active">Active</option><option value="draft">Draft</option><option value="completed">Completed</option></select></div>
<div class="field"><label>Audience</label><select name="audience"><option value="all">Everyone</option><option value="teacher">Teachers</option><option value="pupil">Pupils</option><option value="parent">Parents</option><option value="sponsor">Sponsors</option><option value="admin">Administrators</option></select></div>
<div class="field"><label>Student ID</label><input type="number" name="student_id"></div><div class="field"><label>Class ID</label><input type="number" name="class_id"></div>
<div class="field"><label>Starts at</label><input type="datetime-local" name="starts_at"></div><div class="field"><label>Due at</label><input type="datetime-local" name="due_at"></div>
<div class="field full"><label>Structured data (JSON)</label><textarea name="payload" rows="9" placeholder='{"subject":"Mathematics","description":"Lesson content","videoUrl":"https://...","materialUrl":"https://...","meetingUrl":"https://..."}'></textarea><small class="muted">Store feature-specific data such as ISBN/author, lesson resources, meeting links, periods, stock levels, transport stops and meal menus.</small></div>
<div class="field"><label><input type="checkbox" name="published" value="1" checked> Publish to eligible portals</label></div>
</div><button class="btn" type="submit">Save feature record</button></form></section>
<section class="card"><h2>Existing {{ $features[$feature] }}</h2>
<form method="GET" action="{{ route('admin.features') }}" class="search-row"><input type="hidden" name="feature" value="{{ $feature }}"><input name="search" value="{{ request('search') }}" placeholder="Search records"><button class="btn secondary">Search</button></form>
<div class="table-wrap" style="margin-top:18px"><table class="table"><thead><tr><th>Title</th><th>Status</th><th>Audience</th><th>Published</th><th>Due</th><th>Actions</th></tr></thead><tbody>
@forelse($records as $record)<tr><td><strong>{{ $record->title }}</strong><div class="muted">#{{ $record->id }}</div></td><td><span class="badge">{{ $record->status }}</span></td><td>{{ $record->audience }}</td><td>{{ $record->published?'Yes':'No' }}</td><td>{{ $record->due_at ? \Carbon\Carbon::parse($record->due_at)->format('d M Y H:i') : '—' }}</td><td><form method="POST" action="{{ route('admin.features.archive',$record->id) }}" onsubmit="return confirm('Archive this record?')">@csrf @method('DELETE')<button class="btn ghost" type="submit">Archive</button></form></td></tr>@empty<tr><td colspan="6"><div class="empty">No records yet.</div></td></tr>@endforelse</tbody></table></div>{{ $records->links() }}</section>
</div>
<style>.feature-tabs{display:flex;gap:8px;overflow:auto;padding:4px 0 16px}.feature-tabs a{white-space:nowrap;text-decoration:none;padding:9px 12px;border-radius:999px;background:#eef4fb;color:#52637a;font-size:13px;font-weight:800}.feature-tabs a.active{background:#1769df;color:#fff}.feature-grid{display:grid;grid-template-columns:minmax(360px,.9fr) minmax(500px,1.5fr);gap:18px;margin-top:18px}.search-row{display:grid;grid-template-columns:1fr auto;gap:8px}.search-row input{padding:12px;border:1px solid #d4deea;border-radius:10px}@media(max-width:1050px){.feature-grid{grid-template-columns:1fr}}</style>
@endsection