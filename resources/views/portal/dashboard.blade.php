@extends('layouts.app')
@section('title', ucfirst($user->role).' Dashboard | School Manager')
@section('body')
<div class="admin-shell">
    <header class="admin-top">
        <div>
            <div style="font-size:22px;font-weight:900;letter-spacing:-.03em">School Manager</div>
            <div style="font-size:13px;opacity:.72">{{ ucfirst($profile->portal_type ?? $user->role) }} Portal</div>
        </div>
        <form method="post" action="{{ route('portal.logout') }}">
            @csrf
            <button class="btn secondary" type="submit">Sign out</button>
        </form>
    </header>

    <main class="admin-main" style="max-width:1280px;margin:auto">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:wrap;margin-bottom:28px">
            <div>
                <p class="eyebrow">{{ ucfirst($profile->portal_type ?? $user->role) }} dashboard</p>
                <h1 style="font-size:clamp(32px,5vw,48px);line-height:1.08;letter-spacing:-.04em;margin:7px 0 8px">Welcome, {{ $user->name }}</h1>
                <p class="muted" style="margin:0">A secure overview of the school information available to your account.</p>
            </div>
            <div class="badge">{{ $profile && $profile->active ? 'Account active' : 'Account inactive' }}</div>
        </div>

        @if(!$profile || !$profile->active)
            <div class="notice" style="margin-bottom:22px">
                <strong>Portal access requires an active profile.</strong>
                <p style="margin:4px 0 0">Please contact the school office if your account should have access to this portal.</p>
            </div>
        @endif

        <section class="stat-grid" style="margin-bottom:24px">
            @foreach($dashboardStats as $stat)
                <article class="stat">
                    <span class="muted" style="font-size:13px;font-weight:800">{{ $stat['label'] }}</span>
                    <strong>{{ number_format((int) $stat['value']) }}</strong>
                    <span class="muted" style="font-size:12px">{{ $stat['meta'] }}</span>
                </article>
            @endforeach
        </section>

        @if($profile && $profile->portal_type === 'teacher')
            <div class="split" style="grid-template-columns:1fr 1.35fr;margin-bottom:24px">
                <section class="card">
                    <p class="eyebrow">Teaching profile</p>
                    <h2 style="margin:5px 0 18px">Professional details</h2>
                    <div style="display:grid;gap:13px">
                        <div><span class="muted">Name</span><div style="font-weight:800">{{ $teacher->name ?? $user->name }}</div></div>
                        <div><span class="muted">Email</span><div style="font-weight:800;overflow-wrap:anywhere">{{ $teacher->email ?? $user->email }}</div></div>
                        <div><span class="muted">Phone</span><div style="font-weight:800">{{ $teacher->phone ?: 'Not provided' }}</div></div>
                        <div><span class="muted">Employee number</span><div style="font-weight:800">{{ $teacher->employee_number ?: 'Not assigned' }}</div></div>
                    </div>
                </section>

                <section class="card">
                    <div class="section-head" style="margin-bottom:18px">
                        <div><p class="eyebrow">Current allocation</p><h2>My subjects</h2></div>
                    </div>
                    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px">
                        @forelse($subjects as $subject)
                            <div style="border:1px solid var(--line);border-radius:14px;padding:17px;background:var(--soft)">
                                <h3>{{ $subject->name }}</h3>
                                @if($subject->code)<span class="badge">{{ $subject->code }}</span>@endif
                            </div>
                        @empty
                            <div class="empty" style="grid-column:1/-1">No subjects have been assigned to your teacher profile yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        @elseif($profile && in_array($profile->portal_type, ['pupil','parent','sponsor'], true))
            <section class="card" style="margin-bottom:24px">
                <div class="section-head">
                    <div><p class="eyebrow">{{ $profile->portal_type === 'pupil' ? 'Academic profile' : 'Learner overview' }}</p><h2>{{ $profile->portal_type === 'pupil' ? 'My learner record' : 'Linked learners' }}</h2></div>
                    @if($profile->portal_type !== 'pupil')<span class="badge">{{ $students->count() }} linked</span>@endif
                </div>
                @forelse($students as $student)
                    <article style="border:1px solid var(--line);border-radius:16px;padding:20px;margin-top:14px;background:linear-gradient(135deg,#fff,#f8fbff)">
                        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap">
                            <div>
                                <h3 style="font-size:20px;margin-bottom:4px">{{ $student->name }}</h3>
                                <span class="muted">Admission {{ $student->admission_number }}</span>
                            </div>
                            @if(!empty($student->class_name))<span class="badge">{{ $student->class_name }}</span>@endif
                        </div>
                        <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);margin-top:17px">
                            <div style="padding:14px;border:1px solid var(--line);border-radius:12px;background:#fff"><span class="muted">Attendance</span><strong style="font-size:22px">{{ $studentIds = $students->pluck('id')->count() ? ($attendanceSummary['present'] + $attendanceSummary['absent'] + $attendanceSummary['late'] + $attendanceSummary['excused']) : 0 }}</strong><small class="muted">records</small></div>
                            <div style="padding:14px;border:1px solid var(--line);border-radius:12px;background:#fff"><span class="muted">Present</span><strong style="font-size:22px">{{ $attendanceSummary['present'] }}</strong><small class="muted">records</small></div>
                            <div style="padding:14px;border:1px solid var(--line);border-radius:12px;background:#fff"><span class="muted">Fee balance</span><strong style="font-size:22px">{{ isset($student->fee_balance) ? number_format((float)$student->fee_balance, 2) : '—' }}</strong><small class="muted">KES</small></div>
                        </div>
                    </article>
                @empty
                    <div class="empty"><h3>No learner records linked</h3><p>Your account is active, but no learner record is currently connected to it. The school office can update the relationship or admission link.</p></div>
                @endforelse
            </section>
        @endif

        @if($recentResults->isNotEmpty() || $profile && in_array($profile->portal_type, ['pupil','parent','sponsor','teacher'], true))
            <section class="card" style="margin-bottom:24px">
                <div class="section-head">
                    <div><p class="eyebrow">Academic activity</p><h2>Recent results</h2></div>
                </div>
                @if($recentResults->isNotEmpty())
                    <div class="table-wrap">
                        <table class="table" style="min-width:720px">
                            <thead><tr><th>Learner</th><th>Subject</th><th>Assessment</th><th>Marks</th><th>Grade</th></tr></thead>
                            <tbody>
                                @foreach($recentResults as $result)
                                    <tr>
                                        <td><strong>{{ $result->student_name }}</strong><br><span class="muted">{{ $result->admission_number }}</span></td>
                                        <td>{{ $result->subject_name }}</td>
                                        <td>{{ $result->exam_name }}</td>
                                        <td>{{ number_format((float)$result->marks, 2) }}</td>
                                        <td>@if($result->grade)<span class="badge">{{ $result->grade }}</span>@else—@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty">No academic results have been published for the linked learner records yet.</div>
                @endif
            </section>
        @endif

        <div class="split">
            <section class="card">
                <div class="section-head"><div><p class="eyebrow">School communication</p><h2>Announcements</h2></div></div>
                @forelse($announcements as $announcement)
                    <article style="padding:15px 0;border-bottom:1px solid var(--line)">
                        <h3>{{ $announcement->title }}</h3>
                        <p class="muted" style="margin:3px 0 8px">{{ optional($announcement->published_at ? \Carbon\Carbon::parse($announcement->published_at) : $announcement->created_at)->format('d M Y') }}</p>
                        <p style="margin:0">{{ \Illuminate\Support\Str::limit($announcement->body, 180) }}</p>
                    </article>
                @empty
                    <div class="empty">No published announcements are available.</div>
                @endforelse
            </section>

            <section class="card">
                <div class="section-head"><div><p class="eyebrow">School calendar</p><h2>Upcoming events</h2></div></div>
                @forelse($upcomingEvents as $event)
                    <article style="display:flex;gap:15px;padding:14px 0;border-bottom:1px solid var(--line)">
                        <div class="icon" style="margin:0;flex:0 0 46px">{{ \Carbon\Carbon::parse($event->event_date)->format('d') }}</div>
                        <div><h3>{{ $event->title }}</h3><p class="muted" style="margin:0">{{ \Carbon\Carbon::parse($event->event_date)->format('l, d M Y') }}@if($event->location) · {{ $event->location }}@endif</p></div>
                    </article>
                @empty
                    <div class="empty">No upcoming school events are scheduled.</div>
                @endforelse
            </section>
        </div>
    </main>
</div>
@endsection
