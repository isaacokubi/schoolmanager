@extends('layouts.app')
@section('title','School Portal Hub')
@section('body')
<div class="page-hero"><div class="container"><p class="eyebrow">School Portal</p><h1>Learning & School Hub</h1><p>Access learning content, library resources, communications, notifications, timetable and academic records.</p></div></div>
<section class="section"><div class="container"><div class="grid">
@forelse($records as $record)<article class="card"><span class="badge">{{ ucwords(str_replace('_',' ',$record->feature)) }}</span><h3>{{ $record->title }}</h3><p class="muted">{{ $record->status }} · {{ $record->audience }}</p>
@php($payload=json_decode($record->payload ?: '{}',true))
@if(!empty($payload['description']))<p>{{ $payload['description'] }}</p>@endif
@if(!empty($payload['meetingUrl']))<p><a class="btn secondary" href="{{ $payload['meetingUrl'] }}" target="_blank" rel="noopener">Join online classroom</a></p>@endif
@if(!empty($payload['materialUrl']))<p><a class="btn secondary" href="{{ $payload['materialUrl'] }}" target="_blank" rel="noopener">Open learning material</a></p>@endif
@if(!empty($payload['videoUrl']))<p><video controls preload="metadata" src="{{ $payload['videoUrl'] }}" style="width:100%;border-radius:12px"></video></p>@endif
@if($record->due_at)<small class="muted">Due: {{ \Carbon\Carbon::parse($record->due_at)->format('d M Y H:i') }}</small>@endif
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px">
@if($record->feature==='notifications' && empty($payload['read']))<form method="POST" action="{{ route('portal.features.action',[$record->id,'read']) }}">@csrf<button class="btn secondary" type="submit">Mark as read</button></form>@endif
@if(in_array($record->feature,['learning','lesson_plans']) && empty($payload['completed']))<form method="POST" action="{{ route('portal.features.action',[$record->id,'complete']) }}">@csrf<button class="btn primary" type="submit">Mark complete</button></form>@endif
@if($record->feature==='library' && ($payload['loan_status'] ?? '')!=='borrowed')<form method="POST" action="{{ route('portal.features.action',[$record->id,'borrow']) }}">@csrf<button class="btn secondary" type="submit">Borrow resource</button></form>@elseif($record->feature==='library' && ($payload['loan_status'] ?? '')==='borrowed')<form method="POST" action="{{ route('portal.features.action',[$record->id,'return']) }}">@csrf<button class="btn primary" type="submit">Return resource</button></form>@endif
@if(!empty($payload['completed']))<span class="badge">Completed</span>@endif
@if(!empty($payload['read']))<span class="badge">Read</span>@endif
</div>
</article>@empty<div class="empty">No published portal content is available for this account yet.</div>@endforelse
</div></div></section>
@endsection