@extends('layouts.app')
@section('title', ($profile->portal_type ?? $user->role) === 'teacher' ? 'Teacher Dashboard | '.config('app.name') : ucfirst($user->role).' Portal | '.config('app.name'))
@section('body')
@php
    $portalType = $profile->portal_type ?? $user->role;
    $portalLabel = $portalType === 'teacher' ? 'Teacher Portal' : ucfirst($portalType).' Portal';
    $isTeacher = $portalType === 'teacher';
    $isLearnerPortal = in_array($portalType, ['pupil','parent','sponsor'], true);
    $canPayFees = in_array($portalType, ['parent','sponsor'], true);
    $learnerResultGroups = $recentResults->groupBy('student_id');
@endphp

<div class="portal-shell">
    <header class="portal-header">
        <a class="portal-brand" href="{{ url('/') }}" aria-label="{{ config('app.name') }} home">
            <span class="brand-mark" aria-hidden="true"></span>
            <span class="brand-copy"><strong>{{ config('app.name') }}</strong><small>{{ $portalLabel }}</small></span>
        </a>
        <nav class="portal-actions" aria-label="Portal actions">
            @if($isTeacher)
                <a class="btn secondary" href="{{ route('portal.teacher-learners') }}">My learners</a>
                <a class="btn secondary" href="{{ route('portal.teacher-attendance') }}">Class register</a>
                <a class="btn primary" href="{{ route('portal.teacher-assessments') }}">Enter assessments</a>
                <a class="btn secondary" href="{{ route('portal.signature.index') }}">My signature</a>
            @elseif($canPayFees)
                <a class="btn primary" href="{{ route('portal.payments') }}">M-Pesa payments</a>
            @endif
            <form method="post" action="{{ route('portal.logout') }}">@csrf<button class="btn secondary" type="submit">Sign out</button></form>
        </nav>
    </header>

    <main class="portal-main">
        @if(session('success'))
            <div class="flash success"><strong>Saved successfully</strong><span>{{ session('success') }}</span></div>
        @endif
        @if($errors->any())
            <div class="flash danger"><strong>Please review the request</strong><span>{{ $errors->first() }}</span></div>
        @endif

        <section class="hero {{ $isTeacher ? 'teacher-hero' : '' }}">
            <div>
                <span class="eyebrow">{{ $isTeacher ? 'Academic workspace' : 'Secure learner account' }}</span>
                <h1>Welcome back, {{ $user->name }}</h1>
                <p>{{ $isTeacher ? 'Manage CBC teaching, learner attendance, assessments, report cards and school communication from one secure workspace.' : 'Your academic achievement, attendance, fees and school information in one clear view.' }}</p>
            </div>
            <span class="account {{ $profile && $profile->active ? 'on' : 'off' }}"><i></i>{{ $profile && $profile->active ? 'Account active' : 'Account inactive' }}</span>
        </section>

        @if(!$profile || !$profile->active)
            <div class="alert"><strong>Portal access requires an active profile.</strong><span>Please contact the school office if your account should have access.</span></div>
        @endif

        @if($isTeacher)
            <section class="stats teacher-stats">
                @foreach($dashboardStats as $stat)
                    <a class="stat-card" href="{{ route($stat['route']) }}"><span>{{ $stat['label'] }}</span><strong>{{ is_numeric($stat['value']) ? number_format((int)$stat['value']) : $stat['value'] }}</strong><small>{{ $stat['meta'] }}</small><b>Open →</b></a>
                @endforeach
            </section>
            <section class="quick-actions">
                <div><span class="eyebrow">Daily workflow</span><h2>Teacher workspace</h2><p>Common teaching tasks are one click away.</p></div>
                <div class="action-grid">
                    <a class="action-card primary" href="{{ route('portal.teacher-assessments') }}"><span class="action-icon">✓</span><strong>Assess learners</strong><small>Record marks, missed assessments and CBC levels.</small></a>
                    <a class="action-card" href="{{ route('portal.teacher-attendance') }}"><span class="action-icon">◷</span><strong>Class register</strong><small>Capture attendance for your assigned class pupils.</small></a>
                    <a class="action-card" href="{{ route('portal.teacher-learners') }}"><span class="action-icon">◎</span><strong>View learners</strong><small>Review your learner assessment roster and performance.</small></a>
                    <a class="action-card" href="{{ route('portal.signature.index') }}"><span class="action-icon">✎</span><strong>Manage signature</strong><small>Maintain the teacher signature used for school records.</small></a>
                </div>
            </section>
            <section class="grid two">
                <article class="card"><div class="heading"><div><span class="eyebrow">Professional profile</span><h2>Teaching profile</h2><p class="muted">Your authenticated school staff details.</p></div><a class="text-link" href="{{ route('portal.signature.index') }}">Signature →</a></div><div class="details"><div><span>Name</span><strong>{{ $teacher->name ?? $user->name }}</strong></div><div><span>Email</span><strong>{{ $teacher->email ?? $user->email }}</strong></div><div><span>Phone</span><strong>{{ $teacher->phone ?: 'Not provided' }}</strong></div><div><span>Employee number</span><strong>{{ $teacher->employee_number ?: 'Not assigned' }}</strong></div></div></article>
                <article class="card"><div class="heading"><div><span class="eyebrow">Current allocation</span><h2>Learning areas</h2><p class="muted">Only areas assigned to your teacher account are shown.</p></div><span class="pill">{{ $subjects->count() }} assigned</span></div><div class="subject-list">@forelse($subjects as $subject)<div class="subject"><span class="subject-dot"></span><div><strong>{{ $subject->name }}</strong><small>{{ $subject->code ?: 'CBC learning area' }}</small></div></div>@empty<div class="empty">No learning areas are assigned yet. Contact the school administrator.</div>@endforelse</div></article>
            </section>
            <section class="grid two">
                <article class="card"><div class="heading"><div><span class="eyebrow">Performance overview</span><h2>Learning-area performance</h2><p class="muted">Average marks are calculated from active CBC records.</p></div></div><div class="performance-list">@forelse($teacherPerformance as $performance)@php $avg=$performance->average_marks===null?null:round((float)$performance->average_marks,1);$bar=$avg===null?0:min(100,max(0,$avg)); @endphp<div class="performance-row"><div class="performance-head"><strong>{{ $performance->name }}</strong><span>{{ $avg===null?'No marks':$avg.'/100' }}</span></div><div class="bar"><i style="width:{{ $bar }}%"></i></div><small>{{ number_format((int)$performance->learner_count) }} learners · {{ number_format((int)$performance->result_count) }} records</small></div>@empty<div class="empty">Performance data will appear after assessments are recorded.</div>@endforelse</div><div class="mini-metrics"><div><span>Overall average</span><strong>{{ $teacherAverageMarks===null?'—':number_format($teacherAverageMarks,1).'/100' }}</strong></div><div><span>Missed assessments</span><strong>{{ number_format($teacherMissedCount) }}</strong></div></div></article>
                <article class="card"><div class="heading"><div><span class="eyebrow">Assessment periods</span><h2>Recent exams</h2><p class="muted">Exam activity represented in your learning areas.</p></div><a class="text-link" href="{{ route('portal.teacher-assessments') }}">Manage →</a></div>@forelse($teacherExams as $exam)<div class="list-row exam-row"><div><strong>{{ $exam->name }}</strong><small>{{ $exam->term }} · {{ $exam->academic_year }}</small></div><span>{{ number_format((int)$exam->result_count) }} records</span></div>@empty<div class="empty">No assessment periods have recorded results for your areas.</div>@endforelse</article>
            </section>
            <section class="card"><div class="heading"><div><span class="eyebrow">CBC academic activity</span><h2>Recent achievement records</h2><p class="muted">Achievement levels, points and remarks use the configured CBC scale. Missed assessments are not treated as zero.</p></div><a class="btn small primary" href="{{ route('portal.teacher-assessments') }}">View all assessments</a></div>@if($recentResults->isNotEmpty())<div class="table-wrap"><table><thead><tr><th>Learner</th><th>Learning area</th><th>Assessment</th><th>Marks</th><th>Achievement</th><th>Points</th><th>Teacher remark</th><th>Report card</th></tr></thead><tbody>@foreach($recentResults as $result)<tr><td><strong>{{ $result->student_name }}</strong><small class="code">{{ $result->admission_number }}</small></td><td><strong>{{ $result->subject_name }}</strong><small class="code">{{ $result->subject_code ?: 'CBC learning area' }}</small></td><td>{{ $result->exam_name }}<small class="code">{{ trim(($result->exam_term??'').' '.($result->academic_year??'')) }}</small></td><td>{{ $result->marks===null?'—':number_format((float)$result->marks,1).'/100' }}</td><td><span class="achievement">{{ $result->cbc_code }}</span><small class="code">{{ $result->cbc_label }}</small></td><td>{{ $result->cbc_points===null?'—':$result->cbc_points }}</td><td>{{ $result->cbc_remark }}</td><td><a class="btn small" href="{{ route('portal.report-cards.show',[$result->student_id,$result->exam_id]) }}">View</a><a class="btn secondary small" href="{{ route('portal.report-cards.download',[$result->student_id,$result->exam_id]) }}">PDF</a></td></tr>@endforeach</tbody></table></div>@else<div class="empty">No CBC assessments have been recorded for your learning areas yet.</div>@endif</section>
        @elseif($isLearnerPortal)
            @if($canPayFees)
                <section class="payment-cta"><div><span class="eyebrow">School fees</span><h2>Manage school fees securely</h2><p>Start an M-Pesa STK prompt, authorize it with your M-Pesa PIN and track the resulting receipt.</p></div><a class="btn primary" href="{{ route('portal.payments') }}">Open M-Pesa payments →</a></section>
            @endif

            <section class="stats learner-stats">
                @foreach($dashboardStats as $stat)
                    <article class="stat-card"><span>{{ $stat['label'] }}</span><strong>{{ is_numeric($stat['value']) ? number_format((int)$stat['value']) : $stat['value'] }}</strong><small>{{ $stat['meta'] }}</small></article>
                @endforeach
            </section>

            <section class="profile-card">
                <div class="profile-heading">
                    <div><span class="eyebrow">{{ $portalType==='pupil' ? 'Academic profile' : 'Learner overview' }}</span><h2>{{ $portalType==='pupil' ? 'My learner record' : 'Linked learners' }}</h2><p>{{ $portalType==='pupil' ? 'Your current school identity, attendance and fee information.' : 'Learners connected to this portal account.' }}</p></div>
                    @if($portalType!=='pupil')<span class="pill strong-pill">{{ $students->count() }} linked</span>@endif
                </div>
                <div class="learners">
                    @forelse($students as $student)
                        @php($m=$studentMetrics[$student->id]??['attendance'=>['present'=>0,'absent'=>0,'late'=>0,'excused'=>0],'results'=>0])
                        @php($total=array_sum($m['attendance']))
                        @php($rate=$total?round(($m['attendance']['present']/$total)*100):0)
                        <article class="learner-card">
                            <div class="learner-top"><div><h3>{{ $student->name }}</h3><span>Admission {{ $student->admission_number }}</span></div>@if($student->class_name)<span class="pill">{{ $student->class_name }}</span>@endif</div>
                            <div class="learner-details">
                                <div><span>Attendance records</span><strong>{{ $total }}</strong></div>
                                <div class="attendance-metric {{ $rate < 75 ? 'attention' : 'normal' }}"><span>Present attendance</span><strong>{{ $rate }}%</strong><small>{{ $m['attendance']['present'] }} of {{ $total }} recorded sessions</small></div>
                                <div><span>CBC assessments</span><strong>{{ $m['results'] }}</strong></div>
                                <div class="fee-metric"><span>Fee balance</span><strong>KES {{ number_format((float)$student->fee_balance,2) }}</strong>@if($canPayFees)<small>Payments available via M-Pesa</small>@endif</div>
                            </div>
                            <div class="progress-track" aria-label="{{ $rate }} percent attendance"><i style="width:{{ min(100,$rate) }}%"></i></div>
                        </article>
                    @empty
                        <div class="empty">No learner records are linked to this account. Please contact the school office.</div>
                    @endforelse
                </div>
            </section>

            @if($recentResults->isNotEmpty())
                <section class="card academic-card">
                    <div class="heading"><div><span class="eyebrow">CBC academic activity</span><h2>Achievement record</h2><p class="muted">Learning areas are grouped by assessment period so progress is easier to review.</p></div></div>
                    @foreach($learnerResultGroups as $studentId => $studentResults)
                        @php($studentName=$studentResults->first()->student_name)
                        @php($studentAdmission=$studentResults->first()->admission_number)
                        <div class="student-academic-block">
                            @if($portalType!=='pupil')<div class="student-label"><strong>{{ $studentName }}</strong><span>{{ $studentAdmission }}</span></div>@endif
                            @foreach($studentResults->groupBy('exam_id') as $examId => $examResults)
                                @php($exam=$examResults->first())
                                <div class="exam-card">
                                    <div class="exam-heading"><div><span class="term-label">{{ $exam->exam_term }} · {{ $exam->academic_year }}</span><h3>{{ $exam->exam_name }}</h3><small>{{ $examResults->count() }} learning areas recorded</small></div><div class="exam-actions"><a class="btn small" href="{{ route('portal.report-cards.show',[$exam->student_id,$exam->exam_id]) }}">View report card</a><a class="btn secondary small" href="{{ route('portal.report-cards.download',[$exam->student_id,$exam->exam_id]) }}">Download PDF</a></div></div>
                                    <div class="achievement-grid">
                                        @foreach($examResults as $result)
                                            <article class="achievement-item">
                                                <div class="achievement-subject"><strong>{{ $result->subject_name }}</strong><small>{{ $result->subject_code ?: 'CBC learning area' }}</small></div>
                                                <div class="achievement-score"><span class="score-value">{{ $result->marks===null?'—':number_format((float)$result->marks,1) }}</span><span class="score-denominator">/100</span></div>
                                                <span class="achievement-badge">{{ $result->cbc_code }}</span>
                                                <span class="achievement-separator" aria-hidden="true">•</span>
                                                <span class="points">{{ $result->cbc_points===null?'—':$result->cbc_points.' pts' }}</span>
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </section>
            @else
                <section class="card empty-state"><span class="empty-icon">◎</span><h2>No CBC assessments yet</h2><p>Your recorded learning-area assessments will appear here once the school publishes them.</p></section>
            @endif
        @endif

        <section class="grid two communication-grid">
            <article class="card"><div class="heading"><div><span class="eyebrow">School communication</span><h2>Announcements</h2><p class="muted">Important messages from the school.</p></div></div>@forelse($announcements as $a)<div class="communication-row"><div class="date-badge">{{ \Carbon\Carbon::parse($a->published_at ?: $a->created_at)->format('d') }}<small>{{ \Carbon\Carbon::parse($a->published_at ?: $a->created_at)->format('M') }}</small></div><div><strong>{{ $a->title }}</strong><p>{{ \Illuminate\Support\Str::limit($a->body,180) }}</p></div></div>@empty<div class="empty">No published announcements.</div>@endforelse</article>
            <article class="card"><div class="heading"><div><span class="eyebrow">School calendar</span><h2>Upcoming events</h2><p class="muted">Dates and locations for upcoming school activities.</p></div></div>@forelse($upcomingEvents as $event)@php($date=\Carbon\Carbon::parse($event->event_date))<div class="communication-row"><div class="date-badge calendar">{{ $date->format('d') }}<small>{{ $date->format('M') }}</small></div><div><strong>{{ $event->title }}</strong><p>{{ $date->format('l, d M Y') }}@if($event->location) · {{ $event->location }}@endif</p></div></div>@empty<div class="empty">No upcoming events.</div>@endforelse</article>
        </section>
    </main>
</div>

<style>
.portal-shell{min-height:100vh;background:#f4f7fb;color:#172238}.portal-header{position:sticky;top:0;z-index:30;display:flex;justify-content:space-between;align-items:center;gap:18px;padding:13px 4%;background:#0b2942;border-bottom:1px solid #173e5b;box-shadow:0 8px 28px rgba(8,35,55,.18)}.portal-brand{display:flex;align-items:center;gap:12px;text-decoration:none;color:#fff!important}.brand-mark{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#f4b942,#ffd979);box-shadow:0 5px 14px rgba(0,0,0,.18);position:relative;overflow:hidden}.brand-mark:after{content:"";width:17px;height:17px;border:3px solid #0b2942;border-radius:5px;transform:rotate(45deg);opacity:.9}.brand-copy{display:flex;flex-direction:column;line-height:1.15}.brand-copy strong{font-size:15px}.brand-copy small{margin-top:4px;color:#b9cad9;font-size:11px}.portal-actions{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap}.portal-actions form{margin:0}.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid #cbd7e3;border-radius:9px;background:#fff;color:#17324a!important;padding:9px 13px;font-weight:800;font-size:12px;text-decoration:none;cursor:pointer;transition:.18s ease}.btn:hover{transform:translateY(-1px);box-shadow:0 6px 14px rgba(15,39,64,.12)}.btn.primary{background:#0f766e;border-color:#0f766e;color:#fff!important}.btn.secondary{background:#173e5b;border-color:#2c5775;color:#fff!important}.btn.small{padding:7px 9px;font-size:11px}.portal-main{width:min(1240px,92%);margin:0 auto;padding:30px 0 55px}.hero{display:flex;align-items:flex-end;justify-content:space-between;gap:25px;padding:32px;border-radius:22px;background:linear-gradient(135deg,#0b2942 0%,#124b67 58%,#0f766e 100%);color:#fff;box-shadow:0 18px 42px rgba(11,41,66,.16);margin-bottom:20px}.hero h1{margin:8px 0 8px;font-size:clamp(28px,4vw,42px);letter-spacing:-1px}.hero p{max-width:760px;margin:0;color:#d7e7f1;line-height:1.65}.eyebrow,.kicker{display:inline-block;text-transform:uppercase;letter-spacing:.12em;font-size:10px;font-weight:900;color:#0f766e}.hero .eyebrow{color:#f4c96b}.account{white-space:nowrap;padding:9px 12px;border-radius:999px;background:rgba(255,255,255,.1);font-size:12px;font-weight:800}.account i{display:inline-block;width:7px;height:7px;border-radius:50%;background:#74d7a5;margin-right:6px}.account.off i{background:#ef9a9a}.alert,.flash{display:flex;gap:10px;align-items:flex-start;padding:13px 16px;border-radius:12px;margin-bottom:18px}.alert{background:#fff7df;border:1px solid #ecd28a;color:#664c12}.flash.success{background:#e8f7f1;border:1px solid #b7e1d0;color:#135844}.flash.danger{background:#fff0f0;border:1px solid #efc2c2;color:#7a2525}.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:20px 0}.stat-card{display:flex;flex-direction:column;min-height:130px;padding:19px;border:1px solid #dbe4ed;border-radius:16px;background:#fff;box-shadow:0 7px 22px rgba(23,34,56,.06);text-decoration:none;color:inherit;transition:.18s ease}.stat-card:hover{transform:translateY(-2px);border-color:#b8cbd8}.stat-card span{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#64748b}.stat-card strong{margin:10px 0 4px;font-size:29px;color:#0b2942}.stat-card small{color:#718096;line-height:1.45}.stat-card b{margin-top:auto;font-size:11px;color:#0f766e}.grid.two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-top:18px}.card,.profile-card,.quick-actions,.payment-cta{background:#fff;border:1px solid #dbe4ed;border-radius:18px;box-shadow:0 7px 24px rgba(23,34,56,.055);padding:22px}.heading,.profile-heading,.exam-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:15px}.heading h2,.profile-heading h2{margin:4px 0 5px;font-size:20px;color:#102a43}.heading p,.profile-heading p{margin:0}.muted{color:#718096;font-size:12px;line-height:1.5}.text-link{color:#0f766e;text-decoration:none;font-weight:800;font-size:12px}.pill{display:inline-flex;align-items:center;border-radius:999px;background:#e9f3f5;color:#0f5d5a;padding:6px 10px;font-size:11px;font-weight:900}.strong-pill{background:#dcefeb}.details{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:18px}.details div{padding:13px;border:1px solid #e3eaf0;border-radius:11px;background:#f8fafc}.details span,.metrics span,.learner-details span,.mini-metrics span{display:block;color:#718096;font-size:10px;text-transform:uppercase;letter-spacing:.07em;font-weight:800}.details strong{display:block;margin-top:5px;font-size:13px;word-break:break-word}.subject-list{display:grid;gap:8px;margin-top:16px}.subject{display:flex;gap:10px;align-items:center;padding:10px 12px;border:1px solid #e5ebf0;border-radius:10px}.subject-dot{width:9px;height:9px;border-radius:50%;background:#0f766e}.subject strong,.subject small{display:block}.subject small{color:#718096;font-size:11px;margin-top:2px}.performance-list{display:grid;gap:15px;margin-top:17px}.performance-head{display:flex;justify-content:space-between;font-size:12px}.performance-head span{font-weight:800;color:#0f766e}.bar,.progress-track{height:8px;border-radius:999px;background:#e6edf2;overflow:hidden;margin:7px 0}.bar i,.progress-track i{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#0f766e,#2a9d8f)}.performance-row small{color:#718096;font-size:10px}.mini-metrics{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:18px}.mini-metrics div{padding:13px;border-radius:11px;background:#f5f8fa}.mini-metrics strong{display:block;margin-top:5px}.list-row{padding:13px 0;border-bottom:1px solid #e7edf2}.list-row:last-child{border-bottom:0}.list-row strong,.list-row small{display:block}.list-row small{color:#718096;font-size:11px;margin-top:4px}.list-row>span{color:#0f766e;font-weight:800;font-size:11px}.exam-row{display:flex;justify-content:space-between;gap:12px}.table-wrap{overflow:auto;margin-top:16px}table{width:100%;border-collapse:collapse;font-size:12px}th{background:#f2f6f8;color:#506275;text-transform:uppercase;letter-spacing:.06em;font-size:9px;text-align:left}th,td{padding:11px;border-bottom:1px solid #e4ebf0;vertical-align:top}td small,.code{display:block;color:#718096;font-size:10px;margin-top:3px}.achievement,.achievement-badge{display:inline-flex;padding:5px 7px;border-radius:7px;background:#e7f4f1;color:#0c625d;font-weight:900;font-size:10px}.quick-actions{margin-top:18px;background:linear-gradient(135deg,#fff,#f2f8f7)}.quick-actions h2{margin:4px 0}.quick-actions p{margin:0;color:#718096;font-size:12px}.action-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:17px}.action-card{padding:15px;border:1px solid #dce6ec;border-radius:13px;text-decoration:none;color:#172238;background:#fff}.action-card.primary{border-color:#b7ddd6;background:#eff9f7}.action-icon{display:grid;place-items:center;width:31px;height:31px;border-radius:9px;background:#dff1ee;color:#0f766e;font-weight:900;margin-bottom:11px}.action-card strong,.action-card small{display:block}.action-card small{color:#718096;font-size:10px;line-height:1.5;margin-top:5px}.payment-cta{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px;background:linear-gradient(135deg,#ecf8f5,#fff)}.payment-cta h2{margin:5px 0}.payment-cta p{margin:0;color:#64748b;font-size:12px}.profile-card{margin-top:18px}.learners{display:grid;gap:13px;margin-top:18px}.learner-card{border:1px solid #dce5ec;border-radius:15px;padding:18px;background:#fbfcfd}.learner-top{display:flex;align-items:flex-start;justify-content:space-between;gap:15px}.learner-top h3{margin:0 0 4px;color:#102a43;font-size:18px}.learner-top span{font-size:11px;color:#718096}.learner-details{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:17px}.learner-details>div{padding:13px;border-radius:11px;background:#f3f7f9}.learner-details strong{display:block;color:#102a43;font-size:17px;margin-top:5px}.learner-details small{display:block;color:#718096;font-size:10px;margin-top:4px}.attendance-metric.attention{background:#fff8e8;border:1px solid #f0d99a}.attendance-metric.normal{background:#eef8f5}.fee-metric strong{font-size:16px}.academic-card{margin-top:18px}.student-academic-block+.student-academic-block{margin-top:22px}.student-label{display:flex;align-items:center;justify-content:space-between;padding:11px 13px;margin:0 0 10px;background:#f3f7f9;border-radius:10px}.student-label span{color:#718096;font-size:11px}.exam-card{border:1px solid #dce5ec;border-radius:14px;padding:16px;margin-top:12px}.term-label{display:block;color:#0f766e;text-transform:uppercase;letter-spacing:.07em;font-size:9px;font-weight:900}.exam-heading h3{margin:4px 0;font-size:16px}.exam-heading small{color:#718096;font-size:11px}.exam-actions{display:flex;gap:7px;flex-wrap:wrap}.achievement-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:14px}.achievement-item{display:grid;grid-template-columns:minmax(0,1fr) auto auto auto;gap:9px;align-items:center;padding:11px;background:#f8fafc;border:1px solid #e4ebf0;border-radius:10px}.achievement-subject{min-width:0}.achievement-item strong{font-size:11px}.achievement-item small{display:block;color:#718096;font-size:9px;margin-top:3px}.achievement-score{display:inline-flex;align-items:baseline;gap:2px;white-space:nowrap;padding:5px 7px;border-radius:7px;background:#edf3f6}.achievement-score .score-value{font-size:12px;color:#102a43}.achievement-score .score-denominator{font-size:10px;color:#718096;font-weight:800}.achievement-item .mark{font-size:12px;color:#102a43}.achievement-separator{display:inline-flex;align-items:center;justify-content:center;color:#9aabb9;font-size:12px;font-weight:900;margin:0 -2px}.points{display:inline-flex;align-items:center;justify-content:center;min-width:42px;padding:5px 8px;border-radius:7px;background:#f1f5f7;color:#536779;font-size:10px;font-weight:800;white-space:nowrap}.date-badge{grid-template-rows:1fr auto;padding:5px 0}.date-badge small{display:block;margin-top:1px;line-height:1;font-size:8px;letter-spacing:.08em}.empty{padding:20px;text-align:center;color:#718096;font-size:12px}.empty-state{text-align:center;padding:45px 20px}.empty-icon{display:grid;place-items:center;width:48px;height:48px;margin:0 auto 10px;border-radius:14px;background:#e8f3f2;color:#0f766e;font-size:25px}.empty-state h2{margin:5px}.empty-state p{color:#718096;font-size:12px}.communication-grid{margin-top:20px}.communication-row{display:flex;gap:12px;padding:13px 0;border-bottom:1px solid #e7edf2}.communication-row:last-child{border-bottom:0}.date-badge{flex:0 0 43px;height:43px;border-radius:10px;background:#e9f3f5;color:#0f766e;display:grid;place-items:center;font-weight:900;font-size:15px;line-height:1}.date-badge small{font-size:8px;text-transform:uppercase}.date-badge.calendar{background:#fff2d9;color:#9a6b08}.communication-row strong{font-size:12px}.communication-row p{margin:4px 0 0;color:#718096;font-size:11px;line-height:1.5}.communication-row>div:last-child{min-width:0}
@media(max-width:900px){.stats,.action-grid{grid-template-columns:repeat(2,1fr)}.grid.two{grid-template-columns:1fr}.learner-details{grid-template-columns:repeat(2,1fr)}.achievement-grid{grid-template-columns:1fr}.portal-header{align-items:flex-start}.portal-actions{max-width:65%}.hero{align-items:flex-start;flex-direction:column}}
@media(max-width:620px){.portal-main{width:94%;padding-top:18px}.portal-header{position:static;padding:12px 3%;flex-direction:column}.portal-actions{max-width:none;width:100%;justify-content:flex-start}.portal-actions .btn{flex:1}.hero,.card,.profile-card,.quick-actions,.payment-cta{padding:18px}.stats,.action-grid,.learner-details,.details{grid-template-columns:1fr}.stat-card{min-height:110px}.payment-cta{align-items:flex-start;flex-direction:column}.exam-heading{flex-direction:column}.exam-actions{width:100%}.exam-actions .btn{flex:1}.achievement-item{grid-template-columns:minmax(0,1fr) auto auto}.achievement-score{justify-self:start}.points{min-width:0}.table-wrap{margin-left:-4px;margin-right:-4px}.learner-top{flex-direction:column}.brand-copy strong{font-size:14px}}
</style>
@endsection
