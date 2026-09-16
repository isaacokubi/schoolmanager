@extends('layouts.admin')
@section('title','Admissions | School Manager')
@section('page_title','Admissions')
@section('admin_content')
@php
    $status = request('status');
    $search = trim((string) request('search', ''));
    $statusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
    $statusClasses = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
@endphp

<div class="admin-page-head admissions-head">
    <div>
        <div class="eyebrow">Learner admissions</div>
        <h1>Admissions</h1>
        <p>Review applications, record admission decisions and keep learner intake records up to date.</p>
    </div>
    <a class="btn secondary" href="{{ route('admin.dashboard') }}">← Dashboard</a>
</div>

@if(session('success'))
    <div class="alert success admissions-alert" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert error admissions-alert" role="alert">{{ $errors->first() }}</div>
@endif

<div class="admissions-summary" aria-label="Admissions summary">
    <a class="admissions-summary-card" href="{{ route('admin.admissions.index') }}">
        <span class="summary-icon blue">◌</span>
        <div><strong>{{ $applications->total() }}</strong><span>{{ $search || $status ? 'Applications matching filter' : 'Total applications' }}</span></div>
    </a>
    <a class="admissions-summary-card" href="{{ route('admin.admissions.index', ['status' => 'pending']) }}">
        <span class="summary-icon amber">!</span>
        <div><strong>{{ (int) ($statusCounts['pending'] ?? 0) }}</strong><span>Pending review</span></div>
    </a>
    <a class="admissions-summary-card" href="{{ route('admin.admissions.index', ['status' => 'approved']) }}">
        <span class="summary-icon green">✓</span>
        <div><strong>{{ (int) ($statusCounts['approved'] ?? 0) }}</strong><span>Approved</span></div>
    </a>
    <a class="admissions-summary-card" href="{{ route('admin.admissions.index', ['status' => 'rejected']) }}">
        <span class="summary-icon red">×</span>
        <div><strong>{{ (int) ($statusCounts['rejected'] ?? 0) }}</strong><span>Rejected</span></div>
    </a>
</div>

<div class="card admissions-filter-card">
    <div class="section-head admissions-filter-head">
        <div>
            <h2>Find applications</h2>
            <p>Search by learner or parent/guardian and filter by admission decision.</p>
        </div>
    </div>
    <form method="get" class="admissions-filter-form">
        <div class="filter-field">
            <label for="admissions-search">Search</label>
            <input id="admissions-search" name="search" value="{{ $search }}" placeholder="Learner or parent/guardian name" autocomplete="off">
        </div>
        <div class="filter-field">
            <label for="admissions-status">Status</label>
            <select id="admissions-status" name="status">
                <option value="">All statuses</option>
                @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-actions">
            <button class="btn" type="submit">Filter applications</button>
            @if($search || $status)
                <a class="btn secondary" href="{{ route('admin.admissions.index') }}">Clear</a>
            @endif
        </div>
    </form>
</div>

<div class="card admissions-table-card">
    <div class="section-head admissions-table-head">
        <div>
            <h2>Applications</h2>
            <p>Admission decisions are recorded against each application for an auditable school intake process.</p>
        </div>
        <span class="record-count">{{ $applications->total() }} {{ $applications->total() === 1 ? 'application' : 'applications' }}</span>
    </div>

    <div class="table-wrap admissions-table-wrap">
        <table class="table admissions-table">
            <thead>
                <tr>
                    <th>Learner</th>
                    <th>Parent / Guardian</th>
                    <th>Requested class</th>
                    <th>Status</th>
                    <th class="decision-column">Admission decision</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    @php
                        $applicationStatus = strtolower((string) $application->status);
                        $statusLabel = $statusLabels[$applicationStatus] ?? ucfirst($applicationStatus ?: 'Pending');
                        $statusClass = $statusClasses[$applicationStatus] ?? 'warning';
                    @endphp
                    <tr>
                        <td data-label="Learner">
                            <div class="learner-cell">
                                <div class="learner-avatar">{{ strtoupper(substr(trim((string) $application->student_name), 0, 1)) }}</div>
                                <div>
                                    <strong>{{ $application->student_name }}</strong>
                                    <small class="muted">Date of birth: {{ $application->date_of_birth ? \Carbon\Carbon::parse($application->date_of_birth)->format('d M Y') : 'Not provided' }}</small>
                                </div>
                            </div>
                        </td>
                        <td data-label="Parent / Guardian">
                            <div class="contact-cell">
                                <strong>{{ $application->parent_name ?: 'Not provided' }}</strong>
                                <small class="muted">{{ $application->parent_phone ?: 'No phone number' }}</small>
                            </div>
                        </td>
                        <td data-label="Requested class"><span class="class-chip">{{ $application->requested_class ?: 'Not specified' }}</span></td>
                        <td data-label="Status"><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        <td data-label="Admission decision" class="decision-column">
                            <form method="post" action="{{ route('admin.admissions.status',$application->id) }}" class="decision-form">
                                @csrf
                                @method('PATCH')
                                <label class="sr-only" for="admission-status-{{ $application->id }}">Admission status for {{ $application->student_name }}</label>
                                <select id="admission-status-{{ $application->id }}" name="status">
                                    @foreach($statusLabels as $value => $label)
                                        <option value="{{ $value }}" {{ $applicationStatus === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="btn" type="submit">Save decision</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state admissions-empty">
                                <div class="empty-state-icon">⌕</div>
                                <strong>No applications found</strong>
                                <span>{{ $search || $status ? 'Try changing the search or status filter.' : 'New applications submitted on the public school website will appear here.' }}</span>
                                @if($search || $status)
                                    <a class="btn secondary" href="{{ route('admin.admissions.index') }}">View all applications</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($applications->hasPages())
        <div class="admissions-pagination">{{ $applications->appends(request()->only(['search','status']))->links() }}</div>
    @endif
</div>

<style>
.admissions-head{margin-bottom:20px}.eyebrow{display:inline-flex;align-items:center;margin-bottom:7px;color:#1769d8;font-size:11px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.admissions-head h1{margin-bottom:6px}.admissions-alert{margin-bottom:16px}.admissions-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-bottom:18px}.admissions-summary-card{display:flex;align-items:center;gap:12px;padding:15px 16px;background:#fff;border:1px solid #e4eaf2;border-radius:14px;box-shadow:0 7px 22px rgba(24,45,75,.045);text-decoration:none;transition:transform .15s ease,box-shadow .15s ease,border-color .15s ease}.admissions-summary-card:hover{transform:translateY(-1px);border-color:#cbd9ea;box-shadow:0 10px 26px rgba(24,45,75,.08)}.admissions-summary-card strong{display:block;color:#17243a;font-size:20px;line-height:1.1}.admissions-summary-card span:last-child{display:block;margin-top:4px;color:#7a899c;font-size:11px;font-weight:700}.summary-icon{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;font-weight:900;font-size:17px}.summary-icon.blue{background:#edf4ff;color:#1769df}.summary-icon.amber{background:#fff7df;color:#a86b00}.summary-icon.green{background:#eaf8ef;color:#16844a}.summary-icon.red{background:#fff0ee;color:#c43226}.admissions-filter-card{margin-bottom:18px;padding:0;overflow:hidden}.admissions-filter-head{padding:17px 18px 10px;margin:0}.admissions-filter-head h2,.admissions-table-head h2{font-size:17px;margin:0 0 4px}.admissions-filter-head p,.admissions-table-head p{font-size:12px;color:#7a899c;margin:0}.admissions-filter-form{display:grid;grid-template-columns:minmax(0,1fr) 220px auto;gap:12px;align-items:end;padding:6px 18px 18px}.filter-field label{display:block;color:#52637a;font-size:11px;font-weight:850;margin-bottom:6px}.filter-field input,.filter-field select,.decision-form select{width:100%;min-height:42px;padding:10px 12px;border:1px solid #d8e1ec;border-radius:10px;background:#fff;color:#24364d;font-size:13px;outline:none}.filter-field input:focus,.filter-field select:focus,.decision-form select:focus{border-color:#78a8e9;box-shadow:0 0 0 3px rgba(23,105,223,.1)}.filter-actions{display:flex;gap:8px;align-items:center}.filter-actions .btn{min-height:42px;white-space:nowrap}.admissions-table-card{padding:0;overflow:hidden}.admissions-table-head{display:flex;justify-content:space-between;align-items:center;gap:15px;padding:18px;border-bottom:1px solid #edf1f6;margin:0}.record-count{padding:6px 10px;border-radius:999px;background:#f3f6fa;color:#64758b;font-size:11px;font-weight:850;white-space:nowrap}.admissions-table-wrap{overflow-x:auto}.admissions-table{min-width:980px;margin:0}.admissions-table thead th{background:#f8fafc;color:#65758b;font-size:10px;letter-spacing:.07em;text-transform:uppercase;font-weight:900;padding:12px 15px;border-bottom:1px solid #e7edf4;white-space:nowrap}.admissions-table tbody td{padding:14px 15px;border-bottom:1px solid #eef2f7;vertical-align:middle}.admissions-table tbody tr:last-child td{border-bottom:0}.admissions-table tbody tr:hover{background:#fbfdff}.learner-cell{display:flex;align-items:center;gap:10px;min-width:210px}.learner-avatar{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;flex:0 0 36px;background:linear-gradient(135deg,#eaf2ff,#dceaff);color:#145fc9;font-size:13px;font-weight:900}.learner-cell strong,.contact-cell strong{display:block;color:#1d3048;font-size:13px}.learner-cell small,.contact-cell small{display:block;margin-top:4px;font-size:11px}.class-chip{display:inline-flex;padding:6px 9px;border-radius:8px;background:#f2f6fb;color:#4f627a;font-size:11px;font-weight:800}.decision-column{min-width:250px}.decision-form{display:grid;grid-template-columns:minmax(120px,1fr) auto;gap:7px;align-items:center}.decision-form .btn{white-space:nowrap}.admissions-empty{padding:48px 20px}.empty-state-icon{width:44px;height:44px;margin:0 auto 10px;border-radius:13px;display:grid;place-items:center;background:#edf4ff;color:#1769df;font-size:22px}.admissions-empty .btn{margin-top:12px;display:inline-flex}.admissions-pagination{padding:14px 18px;border-top:1px solid #edf1f6}.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}@media(max-width:950px){.admissions-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.admissions-filter-form{grid-template-columns:1fr 1fr}.filter-actions{grid-column:1/-1}.admissions-table{min-width:900px}}@media(max-width:650px){.admissions-summary{grid-template-columns:1fr}.admissions-filter-form{grid-template-columns:1fr}.filter-actions{grid-column:auto}.admissions-table-head{display:block}.record-count{display:inline-flex;margin-top:10px}.admissions-table{min-width:0}.admissions-table thead{display:none}.admissions-table,.admissions-table tbody,.admissions-table tr,.admissions-table td{display:block;width:100%}.admissions-table tbody tr{padding:14px 15px;border-bottom:1px solid #e8edf3}.admissions-table tbody td{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;padding:9px 0;border:0}.admissions-table tbody td::before{content:attr(data-label);color:#7a899c;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.05em;flex:0 0 105px;padding-top:3px}.admissions-table tbody td:first-child{display:block}.admissions-table tbody td:first-child::before{display:none}.learner-cell{min-width:0}.decision-form{width:min(100%,360px)}.decision-column{min-width:0}.admissions-filter-head{padding-bottom:8px}.filter-actions{display:flex}.filter-actions .btn{flex:1;justify-content:center}.admissions-head .btn{margin-top:12px}}
</style>
@endsection