@extends('layouts.app')
@section('title', ucfirst($user->role).' Dashboard | '.config('app.name'))
@section('body')
@php
    $portalType = $profile->portal_type ?? $user->role;
    $portalLabel = ucfirst($portalType);
    $isLearnerPortal = in_array($portalType, ['pupil','parent','sponsor'], true);
@endphp
<div class="portal-shell">
    <header class="portal-header">
        <a class="portal-brand" href="{{ url('/') }}">
            <span class="portal-brand-mark">{{ strtoupper(substr(config('app.name'), 0, 1)) }}</span>
            <span><strong>{{ config('app.name') }}</strong><small>{{ $portalLabel }} Portal</small></span>
        </a>
        <div class="portal-userbar">
            <div class="portal-user">
                <span class="portal-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                <span><strong>{{ $user->name }}</strong><small>{{ $portalLabel }}</small></span>
            </div>
            <form method="post" action="{{ route('portal.logout') }}">
                @csrf
                <button class="btn secondary" type="submit">Sign out</button>
            </form>
        </div>
    </header>

    <main class="portal-main">
        <section class="portal-hero">
            <div>
                <span class="portal-kicker">{{ $portalLabel }} dashboard</span>
                <h1>Welcome back, {{ $user->name }}</h1>
                <p>{{ $isLearnerPortal ? 'Your academic, attendance and school information at a glance.' : 'Your teaching activity, academic records and school updates at a glance.' }}</p>
            </div>
            <div class="account-pill {{ $profile && $profile->active ? 'active' : 'inactive' }}"><span></span>{{ $profile && $profile->active ? 'Account active' : 'Account inactive' }}</div>
        </section>

        @if(!$profile || !$profile->active)
            <div class="portal-alert"><strong>Portal access requires an active profile.</strong><span>Please contact the school office if your account should have access to this portal.</span></div>
        @endif

        <section class="portal-stats">
            @foreach($dashboardStats as $index => $stat)
                <article class="portal-stat">
                    <div class="stat-icon">{{ ['◉','✓','▣','◆'][$index % 4] }}</div>
                    <div><span>{{ $stat['label'] }}</span><strong>{{ is_numeric($stat['value']) ? number_format((int)$stat['value']) : $stat['value'] }}</strong><small>{{ $stat['meta'] }}</small></div>
                </article>
            @endforeach
        </section>

        @if($portalType === 'teacher')
            <section class="portal-grid two-col">
                <article class="portal-card profile-card">
                    <div class="card-heading"><div><span class="portal-kicker">Professional profile</span><h2>Teaching profile</h2></div><span class="soft-icon">◆</span></div>
                    <div class="profile-list">
                        <div><span>Name</span><strong>{{ $teacher->name ?? $user->name }}</strong></div>
                        <div><span>Email</span><strong class="break">{{ $teacher->email ?? $user->email }}</strong></div>
                        <div><span>Phone</span><strong>{{ $teacher->phone ?: 'Not provided' }}</strong></div>
                        <div><span>Employee number</span><strong>{{ $teacher->employee_number ?: 'Not assigned' }}</strong></div>
                    </div>
                </article>
                <article class="portal-card">
                    <div class="card-heading"><div><span class="portal-kicker">Current allocation</span><h2>My subjects</h2></div><span class="count-pill">{{ $subjects->count() }}</span></div>
                    <div class="subject-grid">
                        @forelse($subjects as $subject)
                            <div class="subject-card"><span class="subject-mark">{{ strtoupper(substr($subject->name, 0, 1)) }}</span><div><strong>{{ $subject->name }}</strong>@if($subject->code)<small>{{ $subject->code }}</small>@endif</div></div>
                        @empty
                            <div class="empty-state">No subjects have been assigned to your teacher profile yet.</div>
                        @endforelse
                    </div>
                </article>
            </section>
        @elseif($isLearnerPortal)
            <section class="portal-card learner-section">
                <div class="card-heading">
                    <div><span class="portal-kicker">{{ $portalType === 'pupil' ? 'Academic profile' : 'Learner overview' }}</span><h2>{{ $portalType === 'pupil' ? 'My learner record' : 'Linked learners' }}</h2></div>
                    @if($portalType !== 'pupil')<span class="count-pill">{{ $students->count() }} linked</span>@endif
                </div>
                <div class="learner-list">
                    @forelse($students as $student)
                        @php
                            $metrics = $studentMetrics[$student->id] ?? ['attendance' => ['present'=>0,'absent'=>0,'late'=>0,'excused'=>0], 'results'=>0];
                            $attendanceTotal = array_sum($metrics['attendance']);
                            $attendanceRate = $attendanceTotal > 0 ? round(($metrics['attendance']['present'] / $attendanceTotal) * 100) : 0;
                            $feeBalance = isset($student->fee_balance) ? (float)$student->fee_balance : null;
                        @endphp
                        <article class="learner-card">
                            <div class="learner-heading">
                                <div class="learner-identity"><span class="learner-avatar">{{ strtoupper(substr($student->name,0,1)) }}</span><div><h3>{{ $student->name }}</h3><p>Admission {{ $student->admission_number }}</p></div></div>
                                @if($student->class_name)<span class="class-pill">{{ $student->class_name }}</span>@endif
                            </div>
                            <div class="learner-metrics">
                                <div class="metric"><span>Attendance</span><strong>{{ number_format($attendanceTotal) }}</strong><small>records</small></div>
                                <div class="metric"><span>Present</span><strong>{{ number_format($metrics['attendance']['present']) }}</strong><small>{{ $attendanceRate }}% rate</small></div>
                                <div class="metric"><span>Results</span><strong>{{ number_format($metrics['results']) }}</strong><small>recorded</small></div>
                                <div class="metric fee"><span>Fee balance</span><strong>{{ $feeBalance !== null ? number_format($feeBalance, 2) : '—' }}</strong><small>KES</small></div>
                            </div>
                            <div class="attendance-bar"><div style="width:{{ min(100, $attendanceRate) }}%"></div></div>
                        </article>
                    @empty
                        <div class="empty-state"><strong>No learner records linked</strong><span>Your account is active, but no learner record is currently connected to it. The school office can update the relationship or admission link.</span></div>
                    @endforelse
                </div>
            </section>
        @endif

        @if($recentResults->isNotEmpty() || $isLearnerPortal || $portalType === 'teacher')
            <section class="portal-card results-section">
                <div class="card-heading"><div><span class="portal-kicker">Academic activity</span><h2>Recent results</h2></div><span class="section-note">Latest recorded assessments</span></div>
                @if($recentResults->isNotEmpty())
                    <div class="result-table-wrap"><table class="result-table"><thead><tr><th>Learner</th><th>Subject</th><th>Assessment</th><th>Marks</th><th>Grade</th></tr></thead><tbody>
                        @foreach($recentResults as $result)
                            <tr><td><strong>{{ $result->student_name }}</strong><small>{{ $result->admission_number }}</small></td><td>{{ $result->subject_name }}</td><td>{{ $result->exam_name }}</td><td><strong>{{ number_format((float)$result->marks, 2) }}</strong></td><td><span class="grade-badge grade-{{ strtolower($result->grade ?: 'na') }}">{{ $result->grade ?: '—' }}</span></td></tr>
                        @endforeach
                    </tbody></table></div>
                @else
                    <div class="empty-state">No academic results have been recorded for the linked learner records yet.</div>
                @endif
            </section>
        @endif

        <section class="portal-grid two-col bottom-grid">
            <article class="portal-card">
                <div class="card-heading"><div><span class="portal-kicker">School communication</span><h2>Announcements</h2></div><span class="soft-icon">!</span></div>
                <div class="announcement-list">
                    @forelse($announcements as $announcement)
                        <article class="announcement"><span class="announcement-dot"></span><div><div class="announcement-meta">{{ \Carbon\Carbon::parse($announcement->published_at ?: $announcement->created_at)->format('d M Y') }}</div><h3>{{ $announcement->title }}</h3><p>{{ \Illuminate\Support\Str::limit($announcement->body, 180) }}</p></div></article>
                    @empty
                        <div class="empty-state">No published announcements are available.</div>
                    @endforelse
                </div>
            </article>
            <article class="portal-card">
                <div class="card-heading"><div><span class="portal-kicker">School calendar</span><h2>Upcoming events</h2></div><span class="soft-icon">◷</span></div>
                <div class="event-list">
                    @forelse($upcomingEvents as $event)
                        @php($eventDate = \Carbon\Carbon::parse($event->event_date))
                        <article class="event-row"><div class="event-date"><strong>{{ $eventDate->format('d') }}</strong><small>{{ $eventDate->format('M') }}</small></div><div><h3>{{ $event->title }}</h3><p>{{ $eventDate->format('l, d M Y') }}@if($event->location) · {{ $event->location }}@endif</p></div></article>
                    @empty
                        <div class="empty-state">No upcoming school events are scheduled.</div>
                    @endforelse
                </div>
            </article>
        </section>
    </main>
</div>
<style>
.portal-shell{min-height:100vh;background:#f4f7fb;color:#152238}.portal-header{height:74px;background:#fff;border-bottom:1px solid #e5ebf3;padding:0 4%;display:flex;align-items:center;justify-content:space-between;gap:20px;position:sticky;top:0;z-index:40;box-shadow:0 5px 20px rgba(20,40,70,.04)}.portal-brand,.portal-user{display:flex;align-items:center;gap:11px;text-decoration:none}.portal-brand strong,.portal-user strong{display:block;font-size:15px;line-height:1.2}.portal-brand small,.portal-user small{display:block;color:#8491a4;font-size:11px;margin-top:3px}.portal-brand-mark,.portal-avatar{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;font-weight:900}.portal-brand-mark{background:linear-gradient(135deg,#0f5bd7,#3283f5);color:#fff;box-shadow:0 8px 18px rgba(15,91,215,.18)}.portal-avatar{background:#eaf2ff;color:#145fc9}.portal-userbar{display:flex;align-items:center;gap:18px}.portal-main{width:min(1240px,92%);margin:0 auto;padding:30px 0 50px}.portal-hero{background:linear-gradient(135deg,#071d3a,#0d4d9e 62%,#1769df);border-radius:22px;padding:30px 34px;color:#fff;display:flex;justify-content:space-between;align-items:center;gap:20px;box-shadow:0 18px 45px rgba(14,55,105,.18);position:relative;overflow:hidden}.portal-hero:after{content:"";position:absolute;width:300px;height:300px;border:55px solid rgba(255,255,255,.06);border-radius:50%;right:-100px;bottom:-150px}.portal-kicker{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.13em;color:#6d9de9}.portal-hero .portal-kicker{color:#bdd7ff}.portal-hero h1{font-size:clamp(28px,4vw,42px);line-height:1.08;letter-spacing:-.04em;margin:7px 0 8px}.portal-hero p{margin:0;color:#dbe9fb;font-size:15px}.account-pill{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);padding:9px 13px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap;position:relative;z-index:2}.account-pill span{width:8px;height:8px;border-radius:50%;background:#67e39a}.account-pill.inactive span{background:#f3b642}.portal-alert{margin:18px 0;padding:14px 17px;background:#fff8e6;border:1px solid #f1dfab;border-left:4px solid #e2a91b;border-radius:12px;display:flex;gap:7px;flex-direction:column}.portal-alert span{font-size:12px;color:#6c7788}.portal-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:20px 0}.portal-stat{background:#fff;border:1px solid #e3eaf3;border-radius:16px;padding:17px;display:flex;align-items:center;gap:13px;box-shadow:0 7px 24px rgba(20,40,70,.045)}.stat-icon,.soft-icon{width:42px;height:42px;border-radius:12px;background:#edf4ff;color:#1769df;display:grid;place-items:center;font-weight:900;flex:0 0 42px}.portal-stat span{display:block;color:#738198;font-size:11px;font-weight:800}.portal-stat strong{display:block;font-size:25px;line-height:1.15;margin:3px 0}.portal-stat small{display:block;color:#99a4b3;font-size:10px}.portal-grid{display:grid;gap:18px}.portal-grid.two-col{grid-template-columns:1fr 1fr}.portal-card{background:#fff;border:1px solid #e2e9f2;border-radius:18px;padding:22px;box-shadow:0 9px 30px rgba(20,40,70,.05);min-width:0}.card-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:15px;margin-bottom:18px}.card-heading h2{margin:3px 0 0;font-size:20px;letter-spacing:-.02em}.count-pill,.class-pill{background:#edf4ff;color:#1769df;border-radius:999px;padding:6px 10px;font-size:11px;font-weight:900}.profile-list{display:grid;grid-template-columns:1fr 1fr;gap:14px}.profile-list div{padding:12px;border:1px solid #edf0f5;border-radius:12px;background:#fafbfd}.profile-list span{display:block;color:#8793a4;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.profile-list strong{display:block;margin-top:4px;font-size:13px}.break{overflow-wrap:anywhere}.subject-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}.subject-card{display:flex;align-items:center;gap:10px;padding:13px;border:1px solid #e8edf4;border-radius:13px;background:#fafbfd}.subject-mark{width:34px;height:34px;border-radius:10px;background:#edf4ff;color:#1769df;display:grid;place-items:center;font-weight:900;font-size:12px}.subject-card strong{display:block;font-size:12px}.subject-card small{display:block;color:#8c98a9;margin-top:2px}.learner-section{margin-top:18px}.learner-list{display:grid;gap:13px}.learner-card{border:1px solid #e2e9f2;border-radius:16px;padding:18px;background:linear-gradient(135deg,#fff,#f9fbfe)}.learner-heading{display:flex;justify-content:space-between;align-items:center;gap:15px}.learner-identity{display:flex;align-items:center;gap:11px}.learner-avatar{width:44px;height:44px;border-radius:13px;background:#eaf2ff;color:#145fc9;display:grid;place-items:center;font-weight:900}.learner-identity h3{margin:0;font-size:17px}.learner-identity p{margin:2px 0 0;color:#8793a4;font-size:11px}.learner-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:16px}.metric{padding:12px;border-radius:12px;background:#fff;border:1px solid #e9eef5}.metric span{display:block;color:#8390a2;font-size:10px;font-weight:800}.metric strong{display:block;font-size:20px;margin:3px 0}.metric small{color:#a0aab8;font-size:10px}.metric.fee strong{font-size:17px}.attendance-bar{height:5px;background:#edf1f6;border-radius:99px;overflow:hidden;margin-top:14px}.attendance-bar div{height:100%;background:#2e9d68;border-radius:99px}.results-section{margin-top:18px}.section-note{color:#9aa5b3;font-size:11px}.result-table-wrap{overflow:auto;border:1px solid #e4eaf1;border-radius:13px}.result-table{width:100%;min-width:720px;border-collapse:collapse}.result-table th,.result-table td{padding:13px 14px;text-align:left;border-bottom:1px solid #edf0f5;font-size:12px}.result-table th{background:#f8fafc;color:#77859a;font-size:10px;text-transform:uppercase;letter-spacing:.06em}.result-table tr:last-child td{border-bottom:0}.result-table td strong{display:block}.result-table td small{display:block;color:#9aa5b3;font-size:10px;margin-top:2px}.grade-badge{display:inline-flex;min-width:31px;justify-content:center;padding:5px 8px;border-radius:8px;background:#edf4ff;color:#1769df;font-weight:900;font-size:11px}.grade-e{background:#fff0f0;color:#b4232d}.grade-d{background:#fff6e8;color:#a86b00}.grade-c{background:#fff9e8;color:#9a7200}.grade-b{background:#eef8f1;color:#267347}.grade-a{background:#e8f7ef;color:#167044}.bottom-grid{margin-top:18px}.announcement-list,.event-list{display:grid}.announcement,.event-row{display:flex;gap:12px;padding:13px 0;border-bottom:1px solid #edf0f5}.announcement:last-child,.event-row:last-child{border-bottom:0}.announcement-dot{width:8px;height:8px;border-radius:50%;background:#1769df;margin-top:6px;flex:0 0 8px}.announcement-meta{font-size:10px;color:#9aa5b3}.announcement h3,.event-row h3{margin:3px 0;font-size:13px}.announcement p,.event-row p{margin:0;color:#758399;font-size:11px;line-height:1.55}.event-date{width:44px;height:47px;border-radius:11px;background:#edf4ff;color:#1769df;display:grid;place-items:center;align-content:center;flex:0 0 44px}.event-date strong{font-size:16px;line-height:1}.event-date small{font-size:9px;text-transform:uppercase;font-weight:900}.empty-state{border:1px dashed #ccd7e5;border-radius:13px;padding:22px;text-align:center;color:#7c899c;background:#fafbfd;font-size:12px}.empty-state strong,.empty-state span{display:block}.empty-state span{margin-top:4px;font-size:11px}.btn.secondary{background:#edf4ff;color:#145fc9;border-color:#dbe8fb}@media(max-width:1000px){.portal-stats{grid-template-columns:repeat(2,1fr)}.portal-grid.two-col{grid-template-columns:1fr}.learner-metrics{grid-template-columns:repeat(2,1fr)}}@media(max-width:650px){.portal-header{height:auto;padding:12px 5%;align-items:flex-start}.portal-userbar{gap:8px}.portal-user{display:none}.portal-main{width:92%;padding-top:20px}.portal-hero{padding:23px 20px;align-items:flex-start;flex-direction:column}.portal-hero h1{font-size:29px}.portal-stats{grid-template-columns:1fr 1fr;gap:9px}.portal-stat{padding:13px}.portal-stat strong{font-size:21px}.profile-list,.subject-grid,.learner-metrics{grid-template-columns:1fr 1fr}.portal-card{padding:17px}.section-note{display:none}}@media(max-width:430px){.portal-stats{grid-template-columns:1fr}.profile-list,.subject-grid,.learner-metrics{grid-template-columns:1fr}.learner-heading{align-items:flex-start;flex-direction:column}.class-pill{align-self:flex-start}.portal-brand strong{font-size:13px}.portal-brand-mark{width:36px;height:36px}.portal-header .btn{padding:9px 12px}}
</style>
@endsection
