@extends('layouts.app')
@section('body')
@php
    $currentRoute = request()->route() ? request()->route()->getName() : '';
    $section = request()->query('section');
    $active = $activeSection ?? null;
    $user = auth()->user();
    $role = strtolower((string) ($user->role ?? 'admin'));
    $roleLabels = ['admin'=>'School Administrator','administrator'=>'School Administrator','superadmin'=>'System Administrator','manager'=>'School Manager','teacher'=>'Teacher','parent'=>'Parent / Guardian','guardian'=>'Parent / Guardian','student'=>'Learner'];
    $roleLabel = $roleLabels[$role] ?? ucwords(str_replace(['_', '-'], ' ', $role));
    $roleBadgeLabels = ['admin'=>'Administrator','administrator'=>'Administrator','superadmin'=>'System Admin','manager'=>'Manager','teacher'=>'Teacher','parent'=>'Parent / Guardian','guardian'=>'Parent / Guardian','student'=>'Learner'];
    $roleBadge = $roleBadgeLabels[$role] ?? $roleLabel;
    $userName = trim((string) ($user->name ?? 'Administrator'));
    if ($userName === '') $userName = $roleLabel;
    $sameAsRole = strcasecmp($userName, $roleLabel) === 0;
    if (!$active) {
        if ($currentRoute === 'admin.dashboard') $active = 'dashboard';
        elseif (strpos($currentRoute, 'admin.students.') === 0) $active = 'students';
        elseif (strpos($currentRoute, 'admin.admissions.') === 0) $active = 'admissions';
        elseif (strpos($currentRoute, 'admin.payments.') === 0) $active = 'payments';
        elseif ($currentRoute === 'admin.reports') $active = 'reports';
        elseif ($currentRoute === 'admin.settings' || $currentRoute === 'admin.settings.update') $active = 'settings';
        elseif ($currentRoute === 'admin.operations' && $section) $active = $section;
        elseif ($currentRoute === 'admin.operations') $active = 'operations';
    }
@endphp
<div class="admin-shell">
<header class="admin-top">
    <div class="admin-top-brand"><div class="admin-mark">{{ strtoupper(substr($settings['school_name'] ?? 'S', 0, 1)) }}</div><div><strong>{{ $settings['school_name'] ?? 'School Manager' }}</strong><small>School Administration Portal</small></div></div>
    <div class="admin-user">@if($sameAsRole)<span>{{ $roleLabel }}</span>@else<span>{{ $userName }}</span><span class="admin-role">{{ $roleBadge }}</span>@endif<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form></div>
</header>
<div class="admin-layout">
<aside class="sidebar">
    <div class="sidebar-section">MAIN</div>
    <a class="{{ $active==='dashboard'?'active':'' }}" href="{{ route('admin.dashboard') }}">⌂ &nbsp; Dashboard</a>

    <div class="sidebar-section">LEARNERS</div>
    <a class="{{ $active==='students'?'active':'' }}" href="{{ route('admin.students.index') }}">◉ &nbsp; Learners</a>
    <a class="{{ $active==='admissions'?'active':'' }}" href="{{ route('admin.admissions.index') }}">＋ &nbsp; Admissions</a>

    <div class="sidebar-section">SCHOOL OPERATIONS</div>
    <a class="{{ $active==='parents'?'active':'' }}" href="{{ route('admin.operations',['section'=>'parents']) }}">♙ &nbsp; Parents / Guardians</a>
    <a class="{{ $active==='classes'?'active':'' }}" href="{{ route('admin.operations',['section'=>'classes']) }}">▦ &nbsp; Classes & Streams</a>
    <a class="{{ $active==='teachers'?'active':'' }}" href="{{ route('admin.operations',['section'=>'teachers']) }}">◆ &nbsp; Teachers</a>
    <a class="{{ $active==='subjects'?'active':'' }}" href="{{ route('admin.operations',['section'=>'subjects']) }}">◇ &nbsp; Learning Areas</a>
    <a class="{{ $active==='attendance'?'active':'' }}" href="{{ route('admin.operations',['section'=>'attendance']) }}">✓ &nbsp; Attendance</a>
    <a class="{{ $active==='exams'?'active':'' }}" href="{{ route('admin.operations',['section'=>'exams']) }}">▤ &nbsp; Assessments</a>
    <a class="{{ $active==='results'?'active':'' }}" href="{{ route('admin.operations',['section'=>'results']) }}">▥ &nbsp; CBC Results</a>
    <a class="{{ $active==='announcements'?'active':'' }}" href="{{ route('admin.operations',['section'=>'announcements']) }}">! &nbsp; Announcements</a>
    <a class="{{ $active==='events'?'active':'' }}" href="{{ route('admin.operations',['section'=>'events']) }}">◷ &nbsp; School Calendar</a>

    <div class="sidebar-section">FINANCE & REPORTING</div>
    <a class="{{ $active==='payments'?'active':'' }}" href="{{ route('admin.payments.index') }}">▣ &nbsp; Fees & Payments</a>
    <a class="{{ $active==='reports'?'active':'' }}" href="{{ route('admin.reports') }}">▥ &nbsp; Reports & Analytics</a>
    <a class="{{ $active==='settings'?'active':'' }}" href="{{ route('admin.settings') }}">⚙ &nbsp; School Settings</a>
    <div class="sidebar-footer"><a href="{{ url('/') }}" target="_blank" rel="noopener">View public website ↗</a></div>
</aside>
<main class="admin-main"><div class="admin-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a><span>/</span><span>@yield('page_title','Administration')</span></div>@yield('admin_content')</main>
</div></div>
<style>
.admin-top{background:#fff;color:#17243a;border-bottom:1px solid #e4eaf2;box-shadow:0 4px 18px rgba(20,40,70,.05);padding:12px 28px;min-height:72px;display:flex;align-items:center;justify-content:space-between;gap:20px;position:sticky;top:0;z-index:60}.admin-top-brand{display:flex;align-items:center;gap:12px}.admin-top-brand strong{display:block;font-size:16px}.admin-top-brand small{display:block;color:#8190a5;font-size:11px}.admin-mark{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:linear-gradient(135deg,#0f5bd7,#3283f5);color:#fff;font-weight:900}.admin-user{display:flex;align-items:center;gap:10px;font-size:13px;font-weight:750}.admin-role{background:#edf4ff;color:#1769d8;border-radius:999px;padding:5px 10px;font-size:11px;white-space:nowrap}.admin-user form{margin-left:5px}.admin-user button{border:1px solid #dce4ef;background:#fff;color:#42536a;padding:8px 12px;border-radius:9px;font-weight:750;cursor:pointer}.admin-user button:hover{background:#f5f8fc;border-color:#c8d5e6;color:#174f99}.admin-layout{display:grid;grid-template-columns:250px minmax(0,1fr);min-height:calc(100vh - 72px)}.sidebar{background:#fff;border-right:1px solid #e4eaf2;padding:18px 12px;position:sticky;top:72px;height:calc(100vh - 72px);overflow:auto}.sidebar-section{padding:15px 12px 7px;color:#9aa7b8;font-size:10px;font-weight:900;letter-spacing:.12em}.sidebar a{display:block;padding:10px 12px;border-radius:10px;text-decoration:none;color:#53637a;font-size:13px;font-weight:700;margin:2px 0;transition:background .15s ease,color .15s ease,box-shadow .15s ease}.sidebar a:hover{background:#f3f7fd;color:#145fc9}.sidebar a.active{background:#eaf2ff;color:#105cc7;box-shadow:inset 3px 0 #1769df;font-weight:850}.sidebar-footer{border-top:1px solid #edf0f5;margin-top:18px;padding:16px 12px}.sidebar-footer a{padding:0;color:#748399;font-size:12px}.admin-main{padding:28px 32px;min-width:0}.admin-breadcrumb{display:flex;gap:8px;color:#94a0b1;font-size:12px;margin-bottom:10px}.admin-breadcrumb a{color:#60728b;text-decoration:none}.admin-main h1{font-size:32px;line-height:1.15;letter-spacing:-.035em;margin:0 0 7px}.admin-page-head{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;margin-bottom:24px}.admin-page-head p{margin:0;color:#748399;font-size:14px}.admin-actions{display:flex;gap:9px;flex-wrap:wrap}.admin-main .stat-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}.admin-main .stat{box-shadow:0 7px 22px rgba(24,45,75,.05);border-radius:15px}.quick-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}.quick-action{background:#fff;border:1px solid #e4eaf2;border-radius:14px;padding:17px;text-decoration:none;box-shadow:0 7px 22px rgba(24,45,75,.045)}.quick-action strong{display:block}.quick-action span{font-size:12px;color:#75849a}.mini-icon{width:36px;height:36px;border-radius:10px;background:#edf4ff;color:#1769df;display:grid;place-items:center;margin-bottom:10px;font-weight:900}.ops-nav{display:flex;gap:6px;flex-wrap:wrap;margin:0 0 18px;padding:7px;background:#fff;border:1px solid #e4eaf2;border-radius:13px}.ops-nav a{padding:9px 12px;border-radius:9px;text-decoration:none;color:#627289;font-size:12px;font-weight:800}.ops-nav a.active{background:#eaf2ff;color:#145fc9}.admin-main .grid-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.admin-main .search{display:grid;grid-template-columns:minmax(0,1fr) auto auto;gap:9px;align-items:center}.admin-main .search input,.admin-main .search select,.admin-main .grid-form input,.admin-main .grid-form select,.admin-main .grid-form textarea{padding:11px 12px;border:1px solid #d8e1ec;border-radius:9px;background:#fff}.admin-main .grid-form label{font-size:12px;font-weight:800;color:#4d5d73}.admin-main .grid-form input,.admin-main .grid-form select,.admin-main .grid-form textarea{margin-top:6px;width:100%}
.admin-live-search-wrap{position:relative}.admin-live-search-results{position:absolute;left:0;right:0;top:calc(100% + 7px);z-index:1200;background:#fff;border:1px solid #d9e3ef;border-radius:13px;box-shadow:0 16px 38px rgba(23,43,73,.16);overflow:hidden;min-width:280px;max-height:360px;overflow-y:auto}.admin-live-search-result{display:block;padding:11px 13px;text-decoration:none;border-bottom:1px solid #eef2f7;color:#1e3149;background:#fff;transition:background .12s ease}.admin-live-search-result:last-child{border-bottom:0}.admin-live-search-result:hover,.admin-live-search-result.is-active{background:#f1f6fd}.admin-live-search-result-title{display:block;font-size:13px;font-weight:850;line-height:1.35}.admin-live-search-result-subtitle{display:block;margin-top:3px;color:#7a899c;font-size:11.5px;line-height:1.35}.admin-live-search-state{padding:15px;color:#6e7d91;font-size:12px;font-weight:700;text-align:center;background:#fff}.admin-live-search-state.loading{color:#1769df}.admin-live-search-state.error{color:#b42318;background:#fff8f7}
@media(max-width:1050px){.admin-main .stat-grid{grid-template-columns:repeat(2,1fr)}.quick-actions{grid-template-columns:repeat(2,1fr)}}@media(max-width:800px){.admin-top{padding:11px 16px}.admin-user span:first-child{display:none}.admin-layout{grid-template-columns:1fr}.sidebar{position:relative;top:0;height:auto;border-right:0;border-bottom:1px solid #e4eaf2;padding:10px}.sidebar-section{display:none}.sidebar a{display:inline-block;margin:3px;padding:9px}.sidebar-footer{display:none}.admin-main{padding:20px 16px}.admin-main .grid-form,.admin-main .search{grid-template-columns:1fr}.admin-page-head{display:block}.admin-actions{margin-top:12px}.quick-actions{grid-template-columns:1fr}.admin-main .table{min-width:700px}.admin-live-search-results{min-width:0;max-height:300px}}@media(max-width:480px){.admin-main h1{font-size:27px}.admin-main .stat-grid{grid-template-columns:1fr}.admin-role{display:none}}
</style>
<script>window.schoolManagerAdminSearchUrl = @json(route('admin.search'));</script><script src="{{ asset('js/admin-live-search.js') }}" defer></script>
@endsection
