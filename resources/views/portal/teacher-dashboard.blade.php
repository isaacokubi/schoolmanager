@extends('layouts.app')
@section('title', 'Teacher Dashboard | '.config('app.name'))
@section('body')
@php
    $today = now()->format('l, d M Y');
    $attendanceTotal = array_sum($attendanceSummary ?? []);
    $attendanceRate = $teacherAttendanceRate;
    $rosterCount = (int) ($teacherRosterCount ?? 0);
    $subjectCount = $subjects->count();
    $resultCount = collect($dashboardStats ?? [])->firstWhere('label', 'CBC records')['value'] ?? 0;
@endphp

<div class="teacher-app">
    <header class="teacher-header">
        <a class="brand" href="{{ url('/') }}" aria-label="{{ config('app.name') }} home">
            <span class="brand-mark">{{ strtoupper(substr(config('app.name'), 0, 1)) }}</span>
            <span><strong>{{ config('app.name') }}</strong><small>Teacher Portal</small></span>
        </a>
        <nav class="header-nav" aria-label="Teacher navigation">
            <a href="{{ route('portal.dashboard') }}" class="active">Dashboard</a>
            <a href="{{ route('portal.teacher-learners') }}">My learners</a>
            <a href="{{ route('portal.teacher-attendance') }}">Attendance</a>
            <a href="{{ route('portal.teacher-assessments') }}">Assessments</a>
            <a href="{{ route('portal.signature.index') }}">My signature</a>
            <form method="post" action="{{ route('portal.logout') }}">@csrf<button type="submit">Sign out</button></form>
        </nav>
    </header>

    <main class="teacher-main">
        @if(session('success'))
            <div class="notice success"><strong>Saved successfully</strong><span>{{ session('success') }}</span></div>
        @endif
        @if($errors->any())
            <div class="notice danger"><strong>Action needs attention</strong><span>{{ $errors->first() }}</span></div>
        @endif

        <section class="hero">
            <div>
                <span class="eyebrow">Academic workspace · {{ $today }}</span>
                <h1>Welcome back, {{ $user->name }}</h1>
                <p>Manage your assigned learners, attendance, CBC assessments, report cards and school communication from one secure workspace.</p>
                <div class="hero-actions">
                    <a class="btn primary" href="{{ route('portal.teacher-assessments') }}">Enter assessments <span>→</span></a>
                    <a class="btn light" href="{{ route('portal.teacher-attendance') }}">Mark attendance</a>
                </div>
            </div>
            <div class="hero-status">
                <span class="status-dot {{ $profile && $profile->active ? 'online' : 'offline' }}"></span>
                <strong>{{ $profile && $profile->active ? 'Account active' : 'Account inactive' }}</strong>
                <small>Teacher access is limited to your assigned learning areas.</small>
            </div>
        </section>

        @if(!$profile || !$profile->active)
            <div class="alert"><strong>Portal access requires an active profile.</strong><span>Please contact the school office if this account should have access.</span></div>
        @endif

        <section class="stats" aria-label="Teaching summary">
            <a class="stat" href="{{ route('portal.teacher-learners') }}"><span>My learners</span><strong>{{ number_format($rosterCount) }}</strong><small>Active learners in your roster</small><b>Open roster →</b></a>
            <a class="stat" href="{{ route('portal.teacher-assessments') }}"><span>Learning areas</span><strong>{{ number_format($subjectCount) }}</strong><small>Current teaching allocation</small><b>Manage areas →</b></a>
            <a class="stat" href="{{ route('portal.teacher-assessments') }}"><span>CBC records</span><strong>{{ is_numeric($resultCount) ? number_format((int)$resultCount) : $resultCount }}</strong><small>Active results in your areas</small><b>Review results →</b></a>
            <a class="stat" href="{{ route('portal.teacher-attendance') }}"><span>Attendance rate</span><strong>{{ $attendanceRate === null ? '—' : $attendanceRate.'%' }}</strong><small>Present / all recorded attendance</small><b>Open register →</b></a>
        </section>

        <section class="section-block">
            <div class="section-heading"><div><span class="eyebrow">Daily workflow</span><h2>Teacher workspace</h2><p>Start the task you need without searching through reports.</p></div></div>
            <div class="actions-grid">
                <a class="action primary" href="{{ route('portal.teacher-assessments') }}"><span class="icon">✓</span><div><strong>Assess learners</strong><small>Record marks, missed assessments, CBC levels and teacher remarks.</small></div><span>→</span></a>
                <a class="action" href="{{ route('portal.teacher-attendance') }}"><span class="icon">◷</span><div><strong>Mark attendance</strong><small>Capture present, absent, late or excused status with notes.</small></div><span>→</span></a>
                <a class="action" href="{{ route('portal.teacher-learners') }}"><span class="icon">◎</span><div><strong>My learner roster</strong><small>Search learners and review attendance and academic performance.</small></div><span>→</span></a>
                <a class="action" href="{{ route('portal.signature.index') }}"><span class="icon">✎</span><div><strong>Manage signature</strong><small>Maintain the signature used for official school report cards.</small></div><span>→</span></a>
                @if($recentResults->isNotEmpty())
                    <a class="action" href="{{ route('portal.report-cards.show', [$recentResults->first()->student_id, $recentResults->first()->exam_id]) }}"><span class="icon">▣</span><div><strong>View report card</strong><small>Open the latest available learner report card.</small></div><span>→</span></a>
                @else
                    <a class="action" href="{{ route('portal.teacher-assessments') }}"><span class="icon">▣</span><div><strong>Report cards</strong><small>Report cards become available after results are recorded.</small></div><span>→</span></a>
                @endif
            </div>
        </section>

        <section class="grid two">
            <article class="card profile-card">
                <div class="card-heading"><div><span class="eyebrow">Professional profile</span><h2>Teaching profile</h2><p>Authenticated staff information.</p></div><a class="link" href="{{ route('portal.signature.index') }}">Signature →</a></div>
                <div class="profile-grid">
                    <div><span>Name</span><strong>{{ $teacher->name ?? $user->name }}</strong></div>
                    <div><span>Employee number</span><strong>{{ $teacher->employee_number ?: 'Not assigned' }}</strong></div>
                    <div><span>Email</span><strong>{{ $teacher->email ?? $user->email }}</strong></div>
                    <div><span>Phone</span><strong>{{ $teacher->phone ?: 'Not provided' }}</strong></div>
                </div>
            </article>
            <article class="card">
                <div class="card-heading"><div><span class="eyebrow">Current allocation</span><h2>Learning areas</h2><p>Only active areas assigned to your account are shown.</p></div><span class="pill">{{ $subjectCount }} assigned</span></div>
                <div class="subject-list">
                    @forelse($subjects as $subject)
                        <a href="{{ route('portal.teacher-assessments', ['subject_id' => $subject->id]) }}" class="subject"><span class="subject-dot"></span><span><strong>{{ $subject->name }}</strong><small>{{ $subject->code ?: 'CBC learning area' }}</small></span><b>Assess →</b></a>
                    @empty
                        <div class="empty">No learning areas are assigned yet. Contact the school administrator.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="grid two">
            <article class="card">
                <div class="card-heading"><div><span class="eyebrow">Operational snapshot</span><h2>Attendance</h2><p>All recorded attendance for your current roster.</p></div><a class="link" href="{{ route('portal.teacher-attendance') }}">Open register →</a></div>
                <div class="attendance-summary">
                    <div class="attendance-rate"><strong>{{ $attendanceRate === null ? '—' : $attendanceRate.'%' }}</strong><span>present rate</span></div>
                    <div class="attendance-counts"><div><b>{{ number_format($attendanceSummary['present'] ?? 0) }}</b><span>Present</span></div><div><b>{{ number_format($attendanceSummary['late'] ?? 0) }}</b><span>Late</span></div><div><b>{{ number_format($attendanceSummary['absent'] ?? 0) }}</b><span>Absent</span></div><div><b>{{ number_format($attendanceSummary['excused'] ?? 0) }}</b><span>Excused</span></div></div>
                </div>
                <div class="scope-note">{{ number_format($attendanceTotal) }} attendance records are currently recorded across your roster. Use the register to capture today's attendance.</div>
            </article>
            <article class="card">
                <div class="card-heading"><div><span class="eyebrow">Assessment follow-up</span><h2>What needs attention</h2><p>Use these indicators to plan your next teaching actions.</p></div><a class="link" href="{{ route('portal.teacher-assessments') }}">Review →</a></div>
                <div class="attention-list">
                    <div><span class="attention-icon">!</span><div><strong>{{ number_format($teacherMissedCount) }} missed assessments</strong><small>Follow up from the assessment register where appropriate.</small></div></div>
                    <div><span class="attention-icon">%</span><div><strong>{{ $teacherAverageMarks === null ? 'No average yet' : number_format($teacherAverageMarks, 1).'/100 overall average' }}</strong><small>Calculated from active marked results in your assigned areas.</small></div></div>
                    <div><span class="attention-icon">◎</span><div><strong>{{ number_format($rosterCount) }} learners in scope</strong><small>Your roster is determined by active assessment records in assigned learning areas.</small></div></div>
                </div>
            </article>
        </section>

        <section class="grid two">
            <article class="card">
                <div class="card-heading"><div><span class="eyebrow">Performance overview</span><h2>Learning-area performance</h2><p>Average marks from active CBC records.</p></div></div>
                <div class="performance-list">
                    @forelse($teacherPerformance as $performance)
                        @php $avg = $performance->average_marks === null ? null : round((float)$performance->average_marks, 1); $bar = $avg === null ? 0 : min(100, max(0, $avg)); @endphp
                        <div class="performance"><div class="performance-top"><strong>{{ $performance->name }}</strong><span>{{ $avg === null ? 'No marks' : $avg.'/100' }}</span></div><div class="bar"><i style="width:{{ $bar }}%"></i></div><small>{{ number_format((int)$performance->learner_count) }} learners · {{ number_format((int)$performance->result_count) }} records</small></div>
                    @empty
                        <div class="empty">Performance data will appear after assessments are recorded.</div>
                    @endforelse
                </div>
            </article>
            <article class="card">
                <div class="card-heading"><div><span class="eyebrow">Assessment periods</span><h2>Recent exams</h2><p>Exam activity represented in your learning areas.</p></div><a class="link" href="{{ route('portal.teacher-assessments') }}">Manage →</a></div>
                @forelse($teacherExams as $exam)
                    <div class="list-row"><div><strong>{{ $exam->name }}</strong><small>{{ $exam->term }} · {{ $exam->academic_year }}</small></div><span>{{ number_format((int)$exam->result_count) }} records</span></div>
                @empty
                    <div class="empty">No assessment periods have recorded results for your areas.</div>
                @endforelse
            </article>
        </section>

        <section class="card results-card">
            <div class="card-heading"><div><span class="eyebrow">CBC academic activity</span><h2>Recent achievement records</h2><p>Teacher-entered remarks are shown separately from the CBC achievement level. Missed assessments are not treated as zero.</p></div><a class="btn small" href="{{ route('portal.teacher-assessments') }}">View all assessments</a></div>
            @if($recentResults->isNotEmpty())
                <div class="table-wrap"><table><thead><tr><th>Learner</th><th>Learning area</th><th>Assessment</th><th>Marks</th><th>Achievement</th><th>Points</th><th>Teacher remark</th><th>Report card</th></tr></thead><tbody>
                @foreach($recentResults as $result)
                    <tr><td><strong>{{ $result->student_name }}</strong><small>{{ $result->admission_number }}</small></td><td><strong>{{ $result->subject_name }}</strong><small>{{ $result->subject_code ?: 'CBC learning area' }}</small></td><td><strong>{{ $result->exam_name }}</strong><small>{{ trim(($result->exam_term ?? '').' '.($result->academic_year ?? '')) }}</small></td><td>{{ $result->marks === null ? '—' : number_format((float)$result->marks, 1).'/100' }}</td><td><span class="achievement">{{ $result->cbc_code }}</span><small>{{ $result->cbc_label }}</small></td><td>{{ $result->cbc_points === null ? '—' : $result->cbc_points }}</td><td class="remark">{{ trim((string)($result->remarks ?? '')) ?: 'No remark recorded' }}</td><td><div class="table-actions"><a class="btn small" href="{{ route('portal.report-cards.show',[$result->student_id,$result->exam_id]) }}">Report card</a><a class="btn secondary small" href="{{ route('portal.report-cards.download',[$result->student_id,$result->exam_id]) }}">PDF</a></div></td></tr>
                @endforeach
                </tbody></table></div>
            @else
                <div class="empty">No CBC assessments have been recorded for your learning areas yet. <a href="{{ route('portal.teacher-assessments') }}">Enter the first assessment →</a></div>
            @endif
        </section>

        <section class="grid two communication-grid">
            <article class="card"><div class="card-heading"><div><span class="eyebrow">School communication</span><h2>Announcements</h2></div></div>@forelse($announcements as $announcement)<div class="list-row announcement"><div><strong>{{ $announcement->title }}</strong><small>{{ \Carbon\Carbon::parse($announcement->published_at ?: $announcement->created_at)->format('d M Y') }}</small></div><p>{{ \Illuminate\Support\Str::limit($announcement->body, 180) }}</p></div>@empty<div class="empty">No published announcements.</div>@endforelse</article>
            <article class="card"><div class="card-heading"><div><span class="eyebrow">School calendar</span><h2>Upcoming events</h2></div></div>@forelse($upcomingEvents as $event)@php($eventDate=\Carbon\Carbon::parse($event->event_date))<div class="list-row event"><span class="date-badge"><b>{{ $eventDate->format('d') }}</b><small>{{ $eventDate->format('M') }}</small></span><div><strong>{{ $event->title }}</strong><small>{{ $eventDate->format('l, d M Y') }}</small></div></div>@empty<div class="empty">No upcoming school events.</div>@endforelse</article>
        </section>
    </main>
</div>

<style>
.teacher-app{min-height:100vh;background:#f4f7fb;color:#172033}.teacher-header{position:sticky;top:0;z-index:20;display:flex;align-items:center;justify-content:space-between;gap:20px;padding:14px clamp(18px,4vw,56px);background:rgba(255,255,255,.96);border-bottom:1px solid #e3e8f0;backdrop-filter:blur(12px)}.brand{display:flex;align-items:center;gap:11px;text-decoration:none;color:#132238;min-width:max-content}.brand-mark{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:#0f766e;color:#fff;font-weight:800;box-shadow:0 8px 20px rgba(15,118,110,.2)}.brand strong{display:block;font-size:15px}.brand small{display:block;color:#64748b;margin-top:2px}.header-nav{display:flex;align-items:center;gap:5px;flex-wrap:wrap;justify-content:flex-end}.header-nav a,.header-nav button{border:0;background:transparent;color:#526176;padding:9px 11px;border-radius:9px;text-decoration:none;font:600 13px inherit;cursor:pointer}.header-nav a:hover,.header-nav a.active,.header-nav button:hover{background:#eef6f5;color:#0f766e}.teacher-main{max-width:1440px;margin:auto;padding:30px clamp(18px,4vw,56px) 60px}.notice,.alert{display:flex;gap:12px;align-items:center;padding:13px 16px;border-radius:12px;margin-bottom:18px;border:1px solid #dbe4ee;background:#fff}.notice strong,.alert strong{display:block}.notice span,.alert span{color:#64748b}.notice.success{border-color:#b7e3d4;background:#effaf6}.notice.danger{border-color:#fecaca;background:#fff7f7}.hero{display:flex;justify-content:space-between;gap:30px;align-items:center;padding:34px;border-radius:22px;background:linear-gradient(135deg,#102a43,#0f766e);color:#fff;box-shadow:0 18px 45px rgba(16,42,67,.16);margin-bottom:22px}.eyebrow{font-size:11px;text-transform:uppercase;letter-spacing:.12em;font-weight:800;color:#0f766e}.hero .eyebrow{color:#a7f3d0}.hero h1{font-size:clamp(28px,4vw,43px);line-height:1.08;margin:8px 0 10px;letter-spacing:-.03em}.hero p{max-width:760px;color:#dbeafe;line-height:1.65;margin:0}.hero-actions{display:flex;gap:10px;margin-top:22px;flex-wrap:wrap}.btn{display:inline-flex;align-items:center;gap:7px;border:1px solid #dbe4ee;border-radius:9px;padding:9px 13px;text-decoration:none;background:#0f766e;color:#fff;font-weight:700;font-size:12px}.btn.primary{background:#fff;color:#0f766e;border-color:#fff}.btn.light{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.28)}.btn.secondary{background:#fff;color:#334155}.btn.small{padding:7px 9px;font-size:11px}.hero-status{min-width:220px;max-width:260px;padding:17px;border-radius:15px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.17)}.hero-status strong{display:block}.hero-status small{display:block;color:#cbd5e1;line-height:1.45;margin-top:6px}.status-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:7px;background:#94a3b8}.status-dot.online{background:#6ee7b7;box-shadow:0 0 0 4px rgba(110,231,183,.12)}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px}.stat{padding:19px;background:#fff;border:1px solid #e2e8f0;border-radius:15px;text-decoration:none;color:#172033;box-shadow:0 7px 20px rgba(15,23,42,.04);transition:.18s}.stat:hover{transform:translateY(-2px);border-color:#b8dcd6}.stat span,.stat small{display:block;color:#64748b}.stat strong{display:block;font-size:30px;letter-spacing:-.04em;margin:7px 0 4px}.stat b{display:block;color:#0f766e;font-size:11px;margin-top:13px}.section-block{margin:28px 0}.section-heading h2,.card-heading h2{margin:4px 0 3px;font-size:20px;letter-spacing:-.02em}.section-heading p,.card-heading p{margin:0;color:#64748b;font-size:13px}.actions-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-top:15px}.action{display:flex;align-items:flex-start;gap:10px;padding:16px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;text-decoration:none;color:#172033;min-height:112px}.action:hover{border-color:#9fd3ca;box-shadow:0 8px 20px rgba(15,118,110,.08)}.action.primary{background:#ecfdf8;border-color:#b7e3d4}.action .icon{display:grid;place-items:center;flex:none;width:31px;height:31px;border-radius:9px;background:#e7f5f2;color:#0f766e;font-weight:800}.action div{flex:1}.action strong{display:block;font-size:13px}.action small{display:block;color:#64748b;line-height:1.45;margin-top:5px}.action>span:last-child{color:#0f766e;font-weight:800}.grid{display:grid;gap:16px;margin-bottom:16px}.grid.two{grid-template-columns:1fr 1fr}.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:21px;box-shadow:0 7px 22px rgba(15,23,42,.035)}.card-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;margin-bottom:17px}.link{color:#0f766e;text-decoration:none;font-weight:700;font-size:12px;white-space:nowrap}.pill{padding:6px 9px;border-radius:99px;background:#ecfdf8;color:#0f766e;font-size:11px;font-weight:800}.profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.profile-grid div{padding:12px;border:1px solid #edf1f5;border-radius:11px;background:#fafbfc}.profile-grid span{display:block;color:#64748b;font-size:11px}.profile-grid strong{display:block;margin-top:5px;font-size:13px;overflow-wrap:anywhere}.subject-list{display:grid;gap:7px}.subject{display:flex;align-items:center;gap:10px;padding:11px 12px;border:1px solid #edf1f5;border-radius:10px;text-decoration:none;color:#172033}.subject:hover{border-color:#b7e3d4;background:#f8fffd}.subject-dot{width:9px;height:9px;border-radius:50%;background:#0f766e;flex:none}.subject span:nth-child(2){flex:1}.subject strong,.subject small{display:block}.subject small{color:#64748b;margin-top:2px}.subject b{font-size:11px;color:#0f766e}.attendance-summary{display:flex;gap:22px;align-items:center}.attendance-rate{min-width:120px;padding:18px;border-radius:13px;background:#ecfdf8}.attendance-rate strong{display:block;font-size:29px;color:#0f766e}.attendance-rate span{font-size:11px;color:#64748b}.attendance-counts{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;flex:1}.attendance-counts div{padding:9px;border-bottom:1px solid #eef2f6}.attendance-counts b{font-size:18px;margin-right:7px}.attendance-counts span{font-size:11px;color:#64748b}.scope-note{font-size:11px;color:#64748b;margin-top:15px;padding-top:13px;border-top:1px solid #eef2f6}.attention-list{display:grid;gap:10px}.attention-list>div{display:flex;gap:10px;padding:11px;border:1px solid #edf1f5;border-radius:11px}.attention-icon{display:grid;place-items:center;width:29px;height:29px;border-radius:9px;background:#fff7ed;color:#c2410c;font-weight:800;flex:none}.attention-list strong,.attention-list small{display:block}.attention-list small{color:#64748b;margin-top:3px;line-height:1.4}.performance-list{display:grid;gap:17px}.performance-top{display:flex;justify-content:space-between;gap:10px;font-size:13px}.performance-top span{color:#0f766e;font-weight:800}.bar{height:7px;background:#e8eef3;border-radius:99px;overflow:hidden;margin:7px 0 5px}.bar i{display:block;height:100%;background:#0f766e;border-radius:99px}.performance small{color:#64748b;font-size:11px}.list-row{display:flex;justify-content:space-between;gap:15px;padding:13px 0;border-bottom:1px solid #edf1f5}.list-row:last-child{border-bottom:0}.list-row strong,.list-row small{display:block}.list-row small{color:#64748b;margin-top:4px}.list-row>span{color:#64748b;font-size:11px;white-space:nowrap}.results-card{margin-bottom:16px}.table-wrap{overflow-x:auto}.table-wrap table{width:100%;border-collapse:collapse;min-width:980px}.table-wrap th{text-align:left;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.08em;background:#f8fafc;padding:11px}.table-wrap td{padding:12px 11px;border-top:1px solid #edf1f5;font-size:12px;vertical-align:top}.table-wrap td strong,.table-wrap td small{display:block}.table-wrap td small{color:#64748b;margin-top:3px}.achievement{display:inline-block!important;padding:4px 7px;border-radius:6px;background:#ecfdf8;color:#0f766e;font-weight:800}.remark{max-width:210px;line-height:1.45;color:#334155}.table-actions{display:flex;gap:5px;flex-wrap:wrap}.communication-grid{margin-bottom:0}.announcement{display:block}.announcement p{font-size:12px;line-height:1.5;color:#475569;margin:7px 0 0}.event{justify-content:flex-start;align-items:center}.date-badge{display:grid!important;place-items:center;width:43px;height:43px;background:#ecfdf8;border-radius:10px;color:#0f766e;flex:none}.date-badge b{font-size:16px!important;margin:0!important}.date-badge small{font-size:9px!important;margin:0!important;text-transform:uppercase}.empty{padding:16px;border:1px dashed #d6dee8;border-radius:10px;color:#64748b;background:#fafbfc;font-size:12px;line-height:1.5}.empty a{color:#0f766e;font-weight:700}.alert{background:#fffaf0;border-color:#fde68a}@media(max-width:1100px){.actions-grid{grid-template-columns:repeat(3,1fr)}.stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:820px){.teacher-header{align-items:flex-start}.header-nav{max-width:55%}.header-nav a{padding:7px}.hero{padding:25px;align-items:flex-start;flex-direction:column}.hero-status{max-width:none;width:100%;box-sizing:border-box}.grid.two{grid-template-columns:1fr}.actions-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.teacher-header{position:relative;display:block}.header-nav{max-width:none;margin-top:12px;justify-content:flex-start;overflow-x:auto;flex-wrap:nowrap}.header-nav a,.header-nav button{white-space:nowrap}.teacher-main{padding-top:18px}.hero{border-radius:16px}.hero h1{font-size:29px}.stats{grid-template-columns:1fr}.actions-grid{grid-template-columns:1fr}.profile-grid{grid-template-columns:1fr}.attendance-summary{align-items:stretch;flex-direction:column}.attendance-rate{min-width:0}.card{padding:16px}.card-heading{flex-direction:column}.card-heading .link,.card-heading .btn{align-self:flex-start}}
</style>
@endsection
