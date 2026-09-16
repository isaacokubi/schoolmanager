@extends('layouts.admin')
@section('title', config('app.name').' | Manager Dashboard')
@section('page_title','Manager Dashboard')
@section('admin_content')
<div class="manager-page">
    <div class="admin-page-head manager-head">
        <div>
            <span class="page-kicker">School operations</span>
            <h1>Good day, {{ auth()->user()->name ?? 'School Manager' }}</h1>
            <p>Coordinate learners, teaching staff, attendance, CBC progress and the school calendar from one operational workspace.</p>
        </div>
        <div class="admin-actions manager-primary-actions">
            <a class="btn secondary" href="{{ route('admin.operations',['section'=>'attendance']) }}"><span>✓</span> Take attendance</a>
            <a class="btn" href="{{ route('admin.operations',['section'=>'results']) }}"><span>＋</span> Record CBC result</a>
        </div>
    </div>

    <div class="manager-banner">
        <div class="banner-copy">
            <span>Operations command centre</span>
            <strong>{{ $settings['school_name'] ?? config('app.name') }}</strong>
            <p>Daily operational view for learner welfare, staffing and academic follow-up.</p>
        </div>
        <div class="manager-date"><small>Today</small><strong>{{ now()->format('D, d M Y') }}</strong></div>
    </div>

    @if((int) ($stats['attendance_today'] ?? 0) === 0 || (int) ($stats['applications'] ?? 0) > 0)
        <div class="manager-attention" role="status">
            <div class="attention-icon">!</div>
            <div class="attention-copy">
                <strong>Operational attention</strong>
                <span>
                    @if((int) ($stats['attendance_today'] ?? 0) === 0 && (int) ($stats['applications'] ?? 0) > 0)
                        No attendance records are showing for today, and there are {{ number_format((int) $stats['applications']) }} admission application{{ (int) $stats['applications'] === 1 ? '' : 's' }} to review.
                    @elseif((int) ($stats['attendance_today'] ?? 0) === 0)
                        No attendance records are showing for today. Confirm that today's register has been completed.
                    @else
                        There are {{ number_format((int) $stats['applications']) }} admission application{{ (int) $stats['applications'] === 1 ? '' : 's' }} requiring review.
                    @endif
                </span>
            </div>
            <div class="attention-actions">
                @if((int) ($stats['attendance_today'] ?? 0) === 0)<a href="{{ route('admin.operations',['section'=>'attendance']) }}">Open attendance</a>@endif
                @if((int) ($stats['applications'] ?? 0) > 0)<a href="{{ route('admin.admissions.index',['status'=>'pending']) }}">Review admissions</a>@endif
            </div>
        </div>
    @endif

    <div class="section-label"><span>School snapshot</span><small>Live operational records</small></div>
    <div class="stat-grid manager-stat-grid">
        <a class="stat manager-stat" href="{{ route('admin.students.index') }}"><span class="stat-icon">◉</span><div><span class="muted">Learners</span><strong>{{ number_format((int) $stats['students']) }}</strong><small>Active learner records</small></div><span class="stat-arrow">→</span></a>
        <a class="stat manager-stat" href="{{ route('admin.operations',['section'=>'teachers']) }}"><span class="stat-icon">◆</span><div><span class="muted">Teachers</span><strong>{{ number_format((int) $stats['teachers']) }}</strong><small>Teaching staff</small></div><span class="stat-arrow">→</span></a>
        <a class="stat manager-stat" href="{{ route('admin.operations',['section'=>'classes']) }}"><span class="stat-icon">▦</span><div><span class="muted">Classes & Streams</span><strong>{{ number_format((int) $stats['classes']) }}</strong><small>Active class records</small></div><span class="stat-arrow">→</span></a>
        <a class="stat manager-stat" href="{{ route('admin.operations',['section'=>'attendance']) }}"><span class="stat-icon">✓</span><div><span class="muted">Attendance Today</span><strong>{{ number_format((int) $stats['attendance_today']) }}</strong><small>Learners marked today</small></div><span class="stat-arrow">→</span></a>
        <a class="stat manager-stat" href="{{ route('admin.admissions.index') }}"><span class="stat-icon">＋</span><div><span class="muted">Admissions</span><strong>{{ number_format((int) $stats['applications']) }}</strong><small>Applications to review</small></div><span class="stat-arrow">→</span></a>
        <a class="stat manager-stat" href="{{ route('admin.operations',['section'=>'parents']) }}"><span class="stat-icon">♙</span><div><span class="muted">Parents / Guardians</span><strong>{{ number_format((int) $stats['parents']) }}</strong><small>Family records</small></div><span class="stat-arrow">→</span></a>
    </div>

    <div class="section-label"><span>Common tasks</span><small>Quick access</small></div>
    <div class="manager-actions">
        <a class="quick-action" href="{{ route('admin.operations',['section'=>'teachers']) }}"><span class="mini-icon">◆</span><div><strong>Manage teachers</strong><span>Review teaching staff and assignments</span></div><b>→</b></a>
        <a class="quick-action" href="{{ route('admin.operations',['section'=>'classes']) }}"><span class="mini-icon">▦</span><div><strong>Manage classes</strong><span>Maintain classes and streams</span></div><b>→</b></a>
        <a class="quick-action" href="{{ route('admin.operations',['section'=>'attendance']) }}"><span class="mini-icon">✓</span><div><strong>Attendance</strong><span>Record and monitor daily attendance</span></div><b>→</b></a>
        <a class="quick-action" href="{{ route('admin.operations',['section'=>'announcements']) }}"><span class="mini-icon">!</span><div><strong>School announcements</strong><span>Publish operational updates</span></div><b>→</b></a>
    </div>

    <div class="grid manager-columns">
        <section class="card dashboard-card">
            <div class="section-head"><div><span class="page-kicker">Academic monitoring</span><h2>Recent CBC achievements</h2><p>Latest learner outcomes requiring routine academic follow-up.</p></div><a class="btn ghost" href="{{ route('admin.operations',['section'=>'results']) }}">Manage results</a></div>
            <div class="table-wrap"><table class="table manager-results-table"><thead><tr><th>Learner</th><th>Learning area</th><th>Assessment</th><th>Achievement</th><th>Points</th></tr></thead><tbody>
            @forelse($cbcRecentResults as $result)
                <tr><td><strong>{{ $result->student_name }}</strong><small>{{ $result->admission_number }}</small></td><td>{{ $result->subject_name }}</td><td>{{ $result->exam_name }}</td><td><span class="achievement">{{ $result->cbc_code }}</span><small>{{ $result->cbc_label }}</small></td><td><strong>{{ $result->cbc_points===null?'—':$result->cbc_points }}</strong></td></tr>
            @empty
                <tr><td colspan="5"><div class="empty-state">No CBC assessments recorded yet.</div></td></tr>
            @endforelse
            </tbody></table></div>
        </section>

        <section class="card dashboard-card">
            <div class="section-head"><div><span class="page-kicker">School calendar</span><h2>Upcoming events</h2><p>Plan ahead for the next school activities.</p></div><a class="btn ghost" href="{{ route('admin.operations',['section'=>'events']) }}">View calendar</a></div>
            @forelse($upcomingEvents as $event)
                <div class="dashboard-list"><div class="date-chip"><strong>{{ \Carbon\Carbon::parse($event->event_date)->format('d') }}</strong><small>{{ \Carbon\Carbon::parse($event->event_date)->format('M') }}</small></div><div><strong>{{ $event->title }}</strong><span>{{ $event->location ?: 'School campus' }}</span></div></div>
            @empty
                <div class="empty-state"><strong>No upcoming events</strong><span>Add school events to keep the operational calendar current.</span></div>
            @endforelse
        </section>
    </div>

    <section class="card manager-focus-card">
        <div class="section-head"><div><span class="page-kicker">Manager focus</span><h2>Daily operational priorities</h2><p>Keep the school day moving with the tasks most relevant to school management.</p></div></div>
        <div class="focus-grid">
            <a href="{{ route('admin.operations',['section'=>'attendance']) }}"><span>✓</span><div><strong>Attendance</strong><small>Check today's learner attendance.</small></div><b>→</b></a>
            <a href="{{ route('admin.operations',['section'=>'teachers']) }}"><span>◆</span><div><strong>Staffing</strong><small>Review teacher records and coverage.</small></div><b>→</b></a>
            <a href="{{ route('admin.operations',['section'=>'results']) }}"><span>▥</span><div><strong>CBC progress</strong><small>Review learning-area achievement.</small></div><b>→</b></a>
            <a href="{{ route('admin.operations',['section'=>'events']) }}"><span>◷</span><div><strong>Calendar</strong><small>Coordinate upcoming school activities.</small></div><b>→</b></a>
        </div>
    </section>
</div>

<style>
.manager-page .page-kicker{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.14em;font-weight:900;color:#1769df;margin-bottom:5px}.manager-head{margin-bottom:20px}.manager-primary-actions .btn span{margin-right:5px}.manager-banner{margin:0 0 17px;padding:20px 22px;border-radius:16px;background:linear-gradient(135deg,#123a2a,#16835a);color:#fff;display:flex;align-items:center;justify-content:space-between;gap:20px;box-shadow:0 12px 30px rgba(20,100,70,.14)}.banner-copy span{display:block;color:#c5f1dc;font-size:10px;text-transform:uppercase;letter-spacing:.12em;font-weight:900}.banner-copy strong{display:block;font-size:19px;margin:3px 0}.banner-copy p{margin:0;color:#def7ea;font-size:11px}.manager-date{min-width:125px;text-align:right;background:rgba(255,255,255,.1);padding:9px 12px;border:1px solid rgba(255,255,255,.12);border-radius:10px}.manager-date small{display:block;color:#c5f1dc;font-size:9px;text-transform:uppercase;letter-spacing:.08em}.manager-date strong{display:block;margin-top:2px;font-size:12px}.manager-attention{display:flex;align-items:center;gap:12px;margin:0 0 18px;padding:12px 14px;border:1px solid #f0dfb2;border-radius:12px;background:#fffaf0}.attention-icon{width:32px;height:32px;flex:0 0 32px;display:grid;place-items:center;border-radius:9px;background:#fff0c7;color:#946000;font-weight:900}.attention-copy{min-width:0;flex:1}.attention-copy strong{display:block;color:#704b00;font-size:12px}.attention-copy span{display:block;margin-top:2px;color:#7d6a49;font-size:11px;line-height:1.45}.attention-actions{display:flex;gap:8px;flex-wrap:wrap}.attention-actions a{padding:7px 9px;border-radius:8px;background:#fff;color:#805500;border:1px solid #ead8a8;text-decoration:none;font-size:10.5px;font-weight:850}.attention-actions a:hover{background:#fff4d8}.section-label{display:flex;align-items:end;justify-content:space-between;gap:15px;margin:4px 0 9px}.section-label span{font-size:12px;text-transform:uppercase;letter-spacing:.1em;font-weight:900;color:#687990}.section-label small{color:#9aa6b5;font-size:10px}.manager-stat-grid{grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:13px;margin-bottom:18px}.manager-stat{display:flex;align-items:center;gap:12px;padding:17px;position:relative;min-height:102px;border:1px solid #e4eaf2}.manager-stat:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(24,45,75,.09)}.manager-stat .stat-icon{background:#edf8f3;color:#16835a}.manager-stat .stat-arrow{color:#16835a}.manager-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}.quick-action{display:flex;align-items:center;gap:11px;min-width:0}.quick-action .mini-icon{flex:0 0 36px;margin:0}.quick-action div{min-width:0;flex:1}.quick-action strong,.quick-action span{display:block}.quick-action strong{font-size:12px;color:#263a52}.quick-action span{margin-top:3px;line-height:1.35}.quick-action b{color:#1769df;font-size:16px}.quick-action:hover{transform:translateY(-2px);border-color:#c5d7ed;box-shadow:0 12px 28px rgba(24,45,75,.08)}.manager-columns{grid-template-columns:1.45fr 1fr;gap:18px}.dashboard-card{padding:21px}.dashboard-card .section-head{margin-bottom:15px}.dashboard-card .section-head h2{font-size:18px}.dashboard-card .section-head p{font-size:12px;line-height:1.45}.manager-results-table{min-width:760px}.manager-results-table th{font-size:10px;letter-spacing:.06em;text-transform:uppercase;color:#718097;background:#f7f9fc}.manager-results-table td{font-size:12px;vertical-align:middle}.manager-results-table td small{display:block;color:#8995a7;font-size:9.5px;margin-top:3px}.achievement{display:inline-flex;padding:5px 7px;border-radius:7px;background:#edf8f3;color:#16835a;font-weight:900;font-size:10px}.dashboard-list{padding:13px 0;border-bottom:1px solid #edf1f6}.dashboard-list:last-child{border-bottom:0}.date-chip{width:45px;height:45px;border:1px solid #dcebe4;background:#f1faf6}.date-chip strong{font-size:16px}.date-chip small{font-size:9px}.dashboard-list>div:last-child strong{font-size:12px}.dashboard-list>div:last-child span{font-size:11px}.manager-focus-card{margin-top:18px;padding:21px}.focus-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.focus-grid a{display:flex;gap:10px;align-items:flex-start;padding:14px;border:1px solid #e4eaf2;border-radius:12px;text-decoration:none;background:#fbfcfe;transition:.18s ease}.focus-grid a:hover{transform:translateY(-2px);border-color:#b9ddcd;box-shadow:0 10px 22px rgba(24,45,75,.07)}.focus-grid a>span{width:34px;height:34px;flex:0 0 34px;display:grid;place-items:center;border-radius:9px;background:#edf8f3;color:#16835a;font-weight:900}.focus-grid a div{min-width:0;flex:1}.focus-grid strong,.focus-grid small{display:block}.focus-grid strong{font-size:12px;color:#23364d}.focus-grid small{margin-top:3px;color:#7b899c;font-size:10px;line-height:1.45}.focus-grid b{color:#16835a;font-size:15px}.empty-state{padding:35px 18px}.empty-state strong{color:#25364e;font-size:14px}.empty-state span{display:block;line-height:1.5}@media(max-width:1050px){.manager-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}.manager-actions,.focus-grid{grid-template-columns:repeat(2,1fr)}.manager-columns{grid-template-columns:1fr}}@media(max-width:700px){.manager-banner{align-items:flex-start;flex-direction:column}.manager-date{min-width:0;text-align:left}.manager-attention{align-items:flex-start;flex-wrap:wrap}.attention-actions{width:100%;padding-left:44px}.attention-actions a{flex:1;text-align:center}.manager-columns{gap:14px}}@media(max-width:480px){.manager-stat-grid,.manager-actions,.focus-grid{grid-template-columns:1fr}.dashboard-card,.manager-focus-card{padding:17px}.manager-primary-actions .btn{width:100%;justify-content:center}.attention-actions{padding-left:0;display:grid;grid-template-columns:1fr}.section-label{align-items:flex-start;flex-direction:column;gap:3px}}
</style>
@endsection
