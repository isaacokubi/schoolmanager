{{--
    PRESENTATION NOTE: admin layout/page.
    Layouts provide shared page structure; content pages provide the information shown
    to visitors. Blade comments document the template without appearing in rendered HTML.
--}}

@extends('layouts.app')

{{-- Define a named content section for the parent layout to render. --}}
@section('title', 'School Manager')

{{-- Define a named content section for the parent layout to render. --}}
@section('body')
{{-- Shared admin shell: individual screens render inside the content section below. --}}
@php
    // The sidebar keeps student and payment features visible, with account settings available to administrators.
    $routeName = optional(request()->route())->getName() ?? '';
    $isStudents = strpos($routeName, 'admin.students.') === 0;
    $isPayments = strpos($routeName, 'admin.payments.') === 0;
    $isAccount = strpos($routeName, 'admin.account.') === 0;
@endphp

{{-- The shell groups the top bar, navigation, and current page content. --}}
<div class="admin-shell">
    <header class="admin-top">
        <a class="admin-top-brand" href="{{ route('admin.students.index') }}">
            <span class="admin-mark">SM</span>
            <span><strong>School Manager</strong><small>Student records and M-Pesa fees</small></span>
        </a>
        <div class="admin-user">
            <span>{{ auth()->user()->name ?? 'Administrator' }}</span>
            <a class="admin-account-link" href="{{ route('admin.account.email') }}" aria-label="Change registered email">Account settings</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </div>
    </header>

    <div class="admin-layout">
        {{-- Active navigation styling is based on the current route name above. --}}
        <aside class="sidebar" aria-label="Main navigation">
            <div class="sidebar-section">PROJECT FEATURES</div>
            <a class="{{ $isStudents ? 'active' : '' }}" href="{{ route('admin.students.index') }}">1. Student records</a>
            <a class="{{ $isPayments ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">2. M-Pesa fee payments</a>
            <div class="sidebar-section" style="margin-top:18px">ACCOUNT</div>
            <a class="{{ $isAccount ? 'active' : '' }}" href="{{ route('admin.account.email') }}">Change registered email</a>
            <div class="sidebar-note">
                This learning project demonstrates student CRUD and a verified M-Pesa payment flow.
            </div>
        </aside>

        <main class="admin-main">
            <div class="admin-breadcrumb">School Manager <span>/</span> @yield('page_title', 'Home')</div>
            {{-- Insert the section content supplied by the child view. --}}
            @yield('admin_content')
        </main>
    </div>
</div>

<style>
/* Shared layout and responsive rules used across administrator pages. */
.admin-shell{min-height:100vh;background:#f5f7fb;color:#17243a}.admin-top{position:sticky;top:0;z-index:10;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 5%;background:#fff;border-bottom:1px solid #e1e8f0}.admin-top-brand{display:flex;align-items:center;gap:12px;color:inherit;text-decoration:none}.admin-mark{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:#145dcc;color:white;font-weight:900}.admin-top-brand strong,.admin-top-brand small{display:block}.admin-top-brand small{font-size:12px;color:#718096}.admin-user{display:flex;align-items:center;gap:14px;font-size:13px}.admin-user form{margin:0}.admin-user button{padding:9px 12px;border:1px solid #d7e0eb;border-radius:9px;background:#fff;cursor:pointer}.admin-account-link{color:#145dcc;font-weight:750;text-decoration:none}.admin-account-link:hover{text-decoration:underline}.admin-layout{display:grid;grid-template-columns:230px minmax(0,1fr);min-height:calc(100vh - 72px)}.sidebar{padding:22px 14px;background:#fff;border-right:1px solid #e1e8f0}.sidebar-section{padding:7px 10px 12px;color:#8290a3;font-size:10px;font-weight:900;letter-spacing:.12em}.sidebar a{display:block;padding:12px 10px;margin-bottom:6px;border-radius:9px;color:#4c5d73;text-decoration:none;font-weight:750;font-size:13px}.sidebar a.active,.sidebar a:hover{background:#eaf2ff;color:#145dcc}.sidebar-note{margin:20px 8px;padding-top:16px;border-top:1px solid #e7edf4;color:#7b899b;font-size:12px;line-height:1.6}.admin-main{min-width:0;padding:28px 4%}.admin-breadcrumb{margin-bottom:18px;color:#8794a6;font-size:12px}.admin-breadcrumb span{padding:0 6px}.admin-main h1{margin:0 0 8px;font-size:30px;letter-spacing:-.03em}.admin-page-head{display:flex;justify-content:space-between;gap:16px;margin-bottom:20px}.admin-page-head p{margin:0;color:#718096}.admin-actions{display:flex;gap:8px;flex-wrap:wrap}.card{padding:20px;background:#fff;border:1px solid #e0e7ef;border-radius:14px;margin-bottom:16px}.btn{display:inline-block;padding:10px 14px;border:0;border-radius:9px;background:#145dcc;color:#fff;text-decoration:none;font-weight:800;cursor:pointer}.btn.secondary{background:#edf3fc;color:#145dcc}.alert{padding:12px 14px;margin-bottom:14px;border-radius:9px}.alert.success{background:#e8f8ef;color:#166534}.alert.error{background:#fff0f0;color:#a51d2d}.table-wrap{overflow-x:auto}.table{width:100%;border-collapse:collapse;min-width:650px}.table th,.table td{padding:12px;text-align:left;border-bottom:1px solid #e8edf3}.table th{background:#f7f9fc;font-size:11px;text-transform:uppercase;letter-spacing:.04em}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.form-grid label,.card form>label{display:block;margin-bottom:12px;font-size:13px;font-weight:750}.form-grid input,.form-grid select,.card form input,.card form select{box-sizing:border-box;width:100%;margin-top:6px;padding:11px;border:1px solid #d3dce8;border-radius:9px;background:#fff}.form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px}.muted{color:#718096;font-size:12px}@media(max-width:760px){.admin-layout{grid-template-columns:1fr}.sidebar{border-right:0;border-bottom:1px solid #e1e8f0;padding:10px}.sidebar-section,.sidebar-note{display:none}.sidebar a{display:inline-block;margin:3px}.admin-main{padding:20px 4%}.admin-page-head{display:block}.admin-actions{margin-top:12px}.form-grid{grid-template-columns:1fr}.admin-user>span{display:none}.admin-user{gap:8px}.admin-account-link{font-size:12px}.admin-user button{padding:8px 9px}}
</style>
@endsection
