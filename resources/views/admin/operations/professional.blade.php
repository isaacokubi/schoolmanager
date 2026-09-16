@extends('layouts.admin')

@section('title', 'School Operations | ' . config('app.name'))
@section('page_title', 'School Operations')

@section('admin_content')
@php
    $labels = [
        'parents' => 'Parents & Guardians', 'classes' => 'Classes & Streams', 'teachers' => 'Teachers',
        'subjects' => 'Learning Areas', 'attendance' => 'Attendance', 'exams' => 'Assessments',
        'results' => 'CBC Results', 'announcements' => 'Announcements', 'events' => 'School Calendar',
    ];
    $descriptions = [
        'parents' => 'Maintain guardian contacts and link guardians to learners.',
        'classes' => 'Organise classes, streams, academic years and class teachers.',
        'teachers' => 'Manage teaching staff, identifiers and learning-area assignments.',
        'subjects' => 'Maintain CBC learning areas and responsible teachers.',
        'attendance' => 'Capture and review daily learner attendance records.',
        'exams' => 'Create assessment periods used for learner results.',
        'results' => 'Record CBC achievement levels and access report cards.',
        'announcements' => 'Publish clear and timely communication to the school community.',
        'events' => 'Manage school events, dates, venues and notices.',
    ];
    $icons = [
        'parents' => 'PG', 'classes' => 'CL', 'teachers' => 'TC', 'subjects' => 'LA',
        'attendance' => 'AT', 'exams' => 'EX', 'results' => 'CB', 'announcements' => 'AN', 'events' => 'CA',
    ];
    $records = ${$section};
    $total = method_exists($records, 'total') ? $records->total() : $records->count();
    $shown = method_exists($records, 'count') ? $records->count() : 0;
    $from = method_exists($records, 'firstItem') ? ($records->firstItem() ?: 0) : ($shown ? 1 : 0);
    $to = method_exists($records, 'lastItem') ? ($records->lastItem() ?: $shown) : $shown;
    $configs = [
        'parents' => ['title' => 'Add parent or guardian', 'fields' => [
            ['name','Full name','text',true,'e.g. Ruth Chebet'], ['phone','Phone number','tel',true,'e.g. 0712345678'],
            ['email','Email address','email',false,'Optional email'], ['relationship','Relationship','text',false,'Mother, Father, Guardian…'],
        ]],
        'classes' => ['title' => 'Add class or stream', 'fields' => [
            ['name','Class name','text',true,'e.g. Grade 6'], ['stream','Stream','text',false,'e.g. East'],
            ['academic_year','Academic year','number',false,'e.g. 2026'],
        ]],
        'teachers' => ['title' => 'Add teacher', 'fields' => [
            ['name','Full name','text',true,'e.g. Jane Wanjiku'], ['email','Email address','email',false,'Optional email'],
            ['phone','Phone number','tel',false,'e.g. 0712345678'], ['employee_number','Employee number','text',false,'e.g. TCH-001'],
        ]],
        'subjects' => ['title' => 'Add learning area', 'fields' => [
            ['name','Learning area','text',true,'e.g. Mathematics'], ['code','Subject code','text',false,'e.g. MAT'],
        ]],
        'exams' => ['title' => 'Create assessment', 'fields' => [
            ['name','Assessment name','text',true,'e.g. Term 2 Assessment'], ['term','Term','text',true,'e.g. Term 2'],
            ['academic_year','Academic year','number',true,'e.g. 2026'], ['start_date','Start date','date',false,''], ['end_date','End date','date',false,''],
        ]],
        'announcements' => ['title' => 'Create announcement', 'fields' => [
            ['title','Announcement title','text',true,'Enter a clear title'], ['body','Message','textarea',true,'Write the message for the school community'],
        ]],
        'events' => ['title' => 'Add school event', 'fields' => [
            ['title','Event title','text',true,'e.g. Parents meeting'], ['event_date','Event date','date',true,''],
            ['location','Location','text',false,'e.g. School hall'], ['description','Description','textarea',false,'Optional details'],
        ]],
    ];
@endphp

<div class="school-ops">
    <header class="ops-hero">
        <div class="hero-copy">
            <span class="eyebrow">School operations</span>
            <h1>Academic &amp; School Records</h1>
            <p>{{ $descriptions[$section] ?? 'Manage academic and administrative records from one secure workspace.' }}</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('admin.dashboard') }}" class="btn-light">← Dashboard</a>
            <a href="#records" class="btn-primary">View records <span>↓</span></a>
        </div>
    </header>

    @if(session('success'))
        <div class="notice success" role="status"><span class="notice-icon">✓</span><div><b>Completed</b><span>{{ session('success') }}</span></div></div>
    @endif
    @if($errors->any())
        <div class="notice error" role="alert"><span class="notice-icon">!</span><div><b>Action could not be completed</b><span>{{ $errors->first() }}</span></div></div>
    @endif

    <nav class="ops-nav" aria-label="School operations sections">
        @foreach($labels as $key => $label)
            <a href="{{ route('admin.operations', ['section' => $key]) }}" class="{{ $section === $key ? 'active' : '' }}">
                <span class="nav-icon">{{ $icons[$key] }}</span><span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>

    <section class="section-heading">
        <div><span class="eyebrow muted">{{ $labels[$section] ?? 'Records' }}</span><h2>{{ $labels[$section] ?? 'School Records' }}</h2><p>{{ $descriptions[$section] ?? '' }}</p></div>
        <div class="stat-card"><span>Active records</span><strong>{{ number_format($total) }}</strong><small>{{ $shown ? 'Showing '.$from.'–'.$to : 'No matching records' }}</small></div>
    </section>

    @if(isset($configs[$section]))
        @php($cfg = $configs[$section])
        <section class="panel create-panel">
            <div class="panel-head"><div><span class="eyebrow muted">Create record</span><h3>{{ $cfg['title'] }}</h3><p>Complete the required fields, then save the record.</p></div><span class="required-note"><b>*</b> Required</span></div>
            <form method="POST" action="{{ route('admin.operations.store') }}" class="ops-form">
                @csrf
                <input type="hidden" name="section" value="{{ $section }}">
                <div class="form-grid">
                    @foreach($cfg['fields'] as $field)
                        <label class="field {{ $field[2] === 'textarea' ? 'wide' : '' }}">
                            <span>{{ $field[1] }} @if($field[3])<b>*</b>@endif</span>
                            @if($field[2] === 'textarea')
                                <textarea name="{{ $field[0] }}" placeholder="{{ $field[4] }}" {{ $field[3] ? 'required' : '' }}>{{ old($field[0]) }}</textarea>
                            @else
                                <input type="{{ $field[2] }}" name="{{ $field[0] }}" value="{{ old($field[0]) }}" placeholder="{{ $field[4] }}" {{ $field[3] ? 'required' : '' }}>
                            @endif
                        </label>
                    @endforeach
                    @if($section === 'parents')
                        <label class="field wide"><span>Link to learner <em>Optional</em></span><select name="student_id"><option value="">Do not link a learner now</option>@foreach($students as $student)<option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>{{ $student->name }} — {{ $student->admission_number }}</option>@endforeach</select><small class="hint">A learner can be linked to this guardian immediately. Additional relationships can be managed from learner records.</small></label>
                    @endif
                    @if($section === 'classes')
                        <label class="field"><span>Class teacher <em>Optional</em></span><select name="class_teacher_id"><option value="">Unassigned</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" {{ old('class_teacher_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}{{ $teacher->employee_number ? ' · '.$teacher->employee_number : '' }}</option>@endforeach</select><small class="hint">Leave unassigned when the class teacher has not yet been appointed.</small></label>
                    @endif
                    @if($section === 'subjects')
                        <label class="field"><span>Assigned teacher <em>Optional</em></span><select name="teacher_id"><option value="">Unassigned</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>@endforeach</select></label>
                    @endif
                    @if($section === 'announcements')
                        <label class="check wide"><input type="checkbox" name="published" value="1" {{ old('published', true) ? 'checked' : '' }}><span><b>Publish immediately</b><small>Clear this option to save the announcement as unpublished.</small></span></label>
                    @endif
                </div>
                <div class="form-footer"><small>Administrator access is required. Data is validated before it is saved.</small><button class="btn-primary" type="submit">Save {{ $labels[$section] ?? 'record' }}</button></div>
            </form>
        </section>
    @endif

    @if($section === 'attendance')
        <section class="panel action-panel">
            <div class="panel-head"><div><span class="eyebrow muted">Daily records</span><h3>Record attendance</h3><p>One learner can have one attendance record per date.</p></div><span class="badge">Present · Absent · Late · Excused</span></div>
            <form method="POST" action="{{ route('admin.operations.attendance') }}" class="ops-form">
                @csrf
                <div class="form-grid">
                    <label class="field wide"><span>Learner <b>*</b></span><select name="student_id" required><option value="">Select learner</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }} — {{ $student->admission_number }}</option>@endforeach</select></label>
                    <label class="field"><span>Date <b>*</b></span><input type="date" name="attendance_date" value="{{ old('attendance_date', date('Y-m-d')) }}" required></label>
                    <label class="field"><span>Status <b>*</b></span><select name="status" required><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option><option value="excused">Excused</option></select></label>
                    <label class="field wide"><span>Notes</span><textarea name="notes" placeholder="Optional attendance note"></textarea></label>
                </div>
                <div class="form-footer"><small>Saving the same learner and date updates the existing attendance record.</small><button class="btn-primary" type="submit">Save attendance</button></div>
            </form>
        </section>
    @endif

    @if($section === 'results')
        <section class="panel cbc action-panel">
            <div class="panel-head"><div><span class="eyebrow muted">Competency-Based Assessment</span><h3>Record CBC assessment</h3><p>Enter a score for completed work or record a missed assessment.</p></div><div class="legend"><b>EE</b> Exceeding <b>ME</b> Meeting <b>AE</b> Approaching <b>BE</b> Below</div></div>
            <form method="POST" action="{{ route('admin.operations.results') }}" id="cbc-form" class="ops-form">
                @csrf
                <div class="form-grid">
                    <label class="field"><span>Assessment <b>*</b></span><select name="exam_id" required><option value="">Select assessment</option>@foreach($exams as $exam)<option value="{{ $exam->id }}">{{ $exam->name }} — {{ $exam->term }} {{ $exam->academic_year }}</option>@endforeach</select></label>
                    <label class="field"><span>Learner <b>*</b></span><select name="student_id" required><option value="">Select learner</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }} — {{ $student->admission_number }}</option>@endforeach</select></label>
                    <label class="field"><span>Learning area <b>*</b></span><select name="subject_id" required><option value="">Select learning area</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></label>
                    <label class="field"><span>Assessment status <b>*</b></span><select name="assessment_status" id="assessment-status" required><option value="present">Assessment completed</option><option value="missed">Missed assessment</option></select></label>
                    <label class="field" id="marks-field"><span>Score (0–100) <b>*</b></span><input type="number" name="marks" id="marks" min="0" max="100" step="0.01" placeholder="Enter score"></label>
                    <label class="field wide"><span>Teacher / assessor remarks</span><textarea name="remarks" placeholder="Optional learning feedback"></textarea></label>
                </div>
                <div class="scale">@foreach([['EE1','90–100'],['EE2','75–89'],['ME1','58–74'],['ME2','41–57'],['AE1','31–40'],['AE2','21–30'],['BE1','11–20'],['BE2','0–10']] as $band)<div><b>{{ $band[0] }}</b><span>{{ $band[1] }}</span></div>@endforeach</div>
                <div class="form-footer"><small>Achievement level and points are calculated by the system.</small><button class="btn-primary" type="submit">Save CBC assessment</button></div>
            </form>
        </section>
    @endif

    <section class="panel records" id="records">
        <div class="panel-head records-head"><div><span class="eyebrow muted">Active records</span><h3>{{ $labels[$section] ?? ucfirst($section) }}</h3><p>Search, review and safely archive records. Archived records are retained rather than permanently deleted.</p></div><span class="count">{{ number_format($total) }} active</span></div>
        <div class="search-area">
            <form method="GET" class="search-form" role="search">
                <input type="hidden" name="section" value="{{ $section }}">
                <div class="search-input"><span aria-hidden="true">⌕</span><input name="search" value="{{ request('search') }}" autocomplete="off" data-live-search="true" aria-label="Search {{ strtolower($labels[$section] ?? 'records') }}" placeholder="Search {{ strtolower($labels[$section] ?? 'records') }}…"></div>
                <button class="btn-primary" type="submit">Search</button>
                @if(request('search'))<a class="btn-muted" href="{{ route('admin.operations', ['section' => $section]) }}">Clear</a>@endif
            </form>
            <div class="search-meta"><span>Tip: search by name, ID, phone, status or related details.</span>@if(request('search'))<strong>Filtering: “{{ request('search') }}”</strong>@endif</div>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Record</th><th>Details</th><th>Status / Context</th><th class="actions-col">Actions</th></tr></thead>
                <tbody>
                @forelse($records as $row)
                    <tr>
                        <td><div class="record-id">#{{ $row->id }}</div><span class="record-date">{{ $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('d M Y') : '—' }}</span></td>
                        <td>
                            @switch($section)
                                @case('parents') <strong>{{ $row->name }}</strong><small>{{ $row->phone }}{{ $row->email ? ' · '.$row->email : '' }}</small> @break
                                @case('classes') <strong>{{ $row->name }}{{ $row->stream ? ' · '.$row->stream : '' }}</strong><small>{{ $row->academic_year ?: 'Academic year not set' }}{{ $row->teacher_name ? ' · '.$row->teacher_name : '' }}</small> @break
                                @case('teachers') <strong>{{ $row->name }}</strong><small>{{ $row->employee_number ?: 'No employee number' }}{{ $row->email ? ' · '.$row->email : '' }}</small> @break
                                @case('subjects') <strong>{{ $row->name }}</strong><small>{{ $row->code ?: 'No code' }}{{ $row->teacher_name ? ' · '.$row->teacher_name : '' }}</small> @break
                                @case('attendance') <strong>{{ $row->student_name ?: 'Learner unavailable' }}</strong><small>{{ $row->admission_number ?: 'No admission number' }} · {{ $row->attendance_date }}</small> @break
                                @case('exams') <strong>{{ $row->name }}</strong><small>{{ $row->term }} · {{ $row->academic_year }}{{ $row->start_date ? ' · '.$row->start_date : '' }}</small> @break
                                @case('results') <strong>{{ $row->student_name ?: 'Learner unavailable' }}</strong><small>{{ $row->admission_number ?: 'No admission number' }} · {{ $row->subject_name ?: 'Learning area unavailable' }}</small> @break
                                @case('announcements') <strong>{{ $row->title }}</strong><small>{{ \Illuminate\Support\Str::limit(strip_tags($row->body), 100) }}</small> @break
                                @case('events') <strong>{{ $row->title }}</strong><small>{{ $row->event_date }}{{ $row->location ? ' · '.$row->location : '' }}</small> @break
                            @endswitch
                        </td>
                        <td>
                            @if($section === 'parents')
                                <span class="status info">{{ (int) $row->learner_count }} {{ (int) $row->learner_count === 1 ? 'learner' : 'learners' }} linked</span>
                            @elseif($section === 'teachers')
                                <span class="status info">{{ (int) $row->subject_count }} {{ (int) $row->subject_count === 1 ? 'learning area' : 'learning areas' }}</span>
                            @elseif($section === 'classes')
                                <span class="status {{ $row->teacher_name ? 'ok' : 'warning' }}">{{ $row->teacher_name ? 'Teacher assigned' : 'Teacher unassigned' }}</span>
                            @elseif($section === 'subjects')
                                <span class="status {{ $row->teacher_name ? 'ok' : 'warning' }}">{{ $row->teacher_name ? 'Teacher assigned' : 'Unassigned' }}</span>
                            @elseif($section === 'attendance')
                                <span class="status {{ $row->status === 'present' ? 'ok' : ($row->status === 'late' ? 'warning' : 'danger') }}">{{ ucfirst($row->status) }}</span>
                            @elseif($section === 'results')
                                <span class="status {{ $row->assessment_status === 'missed' ? 'warning' : 'ok' }}">{{ $row->assessment_status === 'missed' ? 'Missed' : ($row->achievement_level ?: 'Recorded') }}</span>
                                @if($row->marks !== null)<small class="status-sub">{{ number_format((float) $row->marks, 2) }}/100 · {{ $row->exam_name ?: 'Assessment' }}</small>@endif
                            @elseif($section === 'announcements')
                                <span class="status {{ $row->published ? 'ok' : 'warning' }}">{{ $row->published ? 'Published' : 'Draft' }}</span>
                            @elseif($section === 'events')
                                <span class="status info">{{ \Illuminate\Support\Carbon::parse($row->event_date)->isPast() ? 'Past event' : 'Upcoming' }}</span>
                            @else
                                <span class="status info">Active</span>
                            @endif
                        </td>
                        <td class="actions">
                            @if($section === 'results' && !empty($row->student_id) && !empty($row->exam_id))
                                <a class="action-link" href="{{ route('admin.report-cards.show', ['student' => $row->student_id, 'exam' => $row->exam_id]) }}">Report card</a>
                            @endif
                            <form method="POST" action="{{ route('admin.operations.destroy', $row->id) }}" class="archive-form" onsubmit="return confirm('Archive this record? It will be removed from active lists but retained for data safety.');">
                                @csrf @method('DELETE')<input type="hidden" name="section" value="{{ $section }}"><button type="submit" class="archive-btn">Archive</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state"><div class="empty-icon">{{ $icons[$section] }}</div><strong>{{ request('search') ? 'No matching records' : 'No active records yet' }}</strong><p>{{ request('search') ? 'Try a different search term or clear the filter.' : 'Create the first record using the form above.' }}</p>@if(request('search'))<a class="btn-muted" href="{{ route('admin.operations', ['section' => $section]) }}">Clear search</a>@endif</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($records, 'links'))<div class="pagination-wrap">{{ $records->links() }}</div>@endif
    </section>
</div>

@if($section === 'results')
<script>
(function () {
    const status = document.getElementById('assessment-status');
    const marks = document.getElementById('marks');
    const field = document.getElementById('marks-field');
    if (!status || !marks) return;
    function sync() {
        const missed = status.value === 'missed';
        marks.required = !missed;
        marks.disabled = missed;
        if (field) field.classList.toggle('disabled', missed);
        if (missed) marks.value = '';
    }
    status.addEventListener('change', sync); sync();
}());
</script>
@endif

<style>
.school-ops{--navy:#0f2747;--navy-2:#173b68;--blue:#1769aa;--blue-soft:#eaf4ff;--ink:#142338;--muted:#63748a;--line:#dfe7f0;--surface:#fff;--bg:#f4f7fb;--green:#13795b;--green-bg:#e9f8f2;--amber:#9a6700;--amber-bg:#fff7df;--red:#b42318;--red-bg:#fff0ee;color:var(--ink);max-width:1500px;margin:0 auto;padding:8px 4px 42px;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.ops-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:30px;border-radius:20px;background:linear-gradient(135deg,var(--navy),var(--navy-2));box-shadow:0 18px 40px rgba(15,39,71,.16);color:#fff}.eyebrow{display:block;text-transform:uppercase;letter-spacing:.11em;font-size:11px;font-weight:800;color:#9fd2ff}.eyebrow.muted{color:var(--blue);margin-bottom:5px}.hero-copy h1{margin:5px 0 8px;font-size:30px;line-height:1.15;letter-spacing:-.025em}.hero-copy p{margin:0;color:#d8e7f7;max-width:780px;line-height:1.6}.hero-actions{display:flex;gap:10px;align-items:center;flex-shrink:0}.btn-primary,.btn-light,.btn-muted,.action-link,.archive-btn{border:0;border-radius:10px;padding:10px 15px;font-weight:750;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer;transition:.18s ease}.btn-primary{background:var(--blue);color:#fff;box-shadow:0 7px 16px rgba(23,105,170,.2)}.btn-primary:hover{background:#0f5b96;transform:translateY(-1px)}.btn-light{background:#fff;color:var(--navy)}.btn-light:hover{background:#eef6ff}.btn-muted{background:#eef2f6;color:#43546a}.btn-muted:hover{background:#e2e9f1}.notice{display:flex;gap:12px;align-items:flex-start;margin:18px 0;padding:14px 16px;border:1px solid;border-radius:13px}.notice>div{display:grid;gap:3px}.notice b{font-size:13px}.notice span:not(.notice-icon){font-size:13px}.notice-icon{display:grid;place-items:center;width:25px;height:25px;border-radius:50%;font-weight:900}.notice.success{background:var(--green-bg);border-color:#b9ead8;color:#12664e}.notice.success .notice-icon{background:#c9f0e2}.notice.error{background:var(--red-bg);border-color:#f4c5c0;color:#8c2018}.notice.error .notice-icon{background:#ffd8d3}.ops-nav{display:flex;gap:8px;overflow-x:auto;padding:14px 2px 4px;margin-bottom:24px;scrollbar-width:thin}.ops-nav a{display:flex;align-items:center;gap:8px;white-space:nowrap;padding:10px 12px;border:1px solid var(--line);border-radius:11px;background:#fff;color:#516277;text-decoration:none;font-size:12px;font-weight:750}.ops-nav a:hover{border-color:#b9cde1;color:var(--navy);background:#f8fbff}.ops-nav a.active{background:var(--blue-soft);border-color:#a9d0f1;color:#0d5d99}.nav-icon{display:grid;place-items:center;min-width:24px;height:24px;border-radius:7px;background:#edf2f7;color:var(--navy);font-size:9px;font-weight:900}.active .nav-icon{background:#d4e9fa;color:#0d5d99}.section-heading{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin:4px 0 18px}.section-heading h2{margin:0;font-size:24px;letter-spacing:-.02em}.section-heading p{margin:5px 0 0;color:var(--muted);font-size:13px;max-width:800px}.stat-card{min-width:160px;padding:14px 16px;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 7px 20px rgba(20,35,56,.05)}.stat-card span,.stat-card small{display:block;color:var(--muted);font-size:11px}.stat-card strong{display:block;font-size:24px;color:var(--navy);margin:3px 0}.panel{background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:0 8px 26px rgba(20,35,56,.055);margin-bottom:20px;overflow:hidden}.panel.create-panel,.panel.action-panel{padding:22px}.panel-head{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:18px}.panel-head h3{margin:0;font-size:18px}.panel-head p{margin:5px 0 0;color:var(--muted);font-size:12px}.required-note{font-size:11px;color:var(--muted)}.required-note b,.field>b{color:var(--red)}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.field{display:grid;gap:7px;font-size:12px;font-weight:750}.field.wide{grid-column:1/-1}.field em{font-style:normal;color:#8a98aa;font-weight:500;font-size:10px}.field input,.field select,.field textarea{width:100%;box-sizing:border-box;border:1px solid #cfd9e5;border-radius:10px;background:#fff;color:var(--ink);padding:11px 12px;font:inherit;font-weight:500;outline:0}.field input:focus,.field select:focus,.field textarea:focus{border-color:#6eaddb;box-shadow:0 0 0 3px #e8f4fd}.field textarea{min-height:100px;resize:vertical}.field .hint{color:var(--muted);font-size:10px;font-weight:500;line-height:1.4}.check{display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid var(--line);border-radius:10px;background:#f8fafc}.check input{margin-top:3px}.check span{display:grid;gap:2px}.check small{font-weight:500;color:var(--muted)}.form-footer{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:18px;padding-top:16px;border-top:1px solid #edf1f5}.form-footer small{color:var(--muted);font-size:11px}.badge{padding:7px 10px;border-radius:999px;background:var(--blue-soft);color:#17619c;font-size:10px;font-weight:800}.legend{display:flex;gap:8px;align-items:center;flex-wrap:wrap;color:var(--muted);font-size:10px}.legend b{color:var(--navy);background:#edf3f8;border-radius:6px;padding:4px 6px}.scale{display:grid;grid-template-columns:repeat(8,1fr);gap:7px;margin-top:15px}.scale div{border:1px solid var(--line);border-radius:9px;padding:8px;text-align:center;background:#fafcfe}.scale b{display:block;color:var(--navy);font-size:11px}.scale span{display:block;color:var(--muted);font-size:9px;margin-top:2px}.field.disabled{opacity:.55}.records{margin-top:20px}.records .panel-head{padding:22px 22px 0;margin-bottom:0}.records-head .count{padding:7px 10px;border-radius:999px;background:#eef4f9;color:#35526f;font-size:11px;font-weight:800;white-space:nowrap}.search-area{padding:17px 22px;background:#f8fafc;border-top:1px solid #edf1f5;border-bottom:1px solid #e6edf4}.search-form{display:flex;gap:9px;align-items:center}.search-input{display:flex;align-items:center;gap:8px;flex:1;min-width:0;background:#fff;border:1px solid #cfd9e5;border-radius:10px;padding:0 11px}.search-input:focus-within{border-color:#6eaddb;box-shadow:0 0 0 3px #e8f4fd}.search-input span{color:#73859a;font-size:20px}.search-input input{border:0;outline:0;width:100%;padding:11px 0;background:transparent;color:var(--ink);font-size:13px}.search-meta{display:flex;justify-content:space-between;gap:12px;margin-top:7px;color:#7b8999;font-size:10px}.search-meta strong{color:#49627a;font-weight:700}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:850px}th{padding:12px 16px;text-align:left;background:#fbfcfe;border-bottom:1px solid var(--line);color:#6c7c90;font-size:10px;text-transform:uppercase;letter-spacing:.07em}td{padding:14px 16px;border-bottom:1px solid #edf1f5;vertical-align:middle;font-size:12px}tbody tr:hover{background:#fbfdff}.record-id{font-weight:850;color:var(--navy)}.record-date{display:block;color:#8a98a8;font-size:9px;margin-top:3px}td strong{display:block;font-size:12px;color:#1c2e43}td small{display:block;color:var(--muted);font-size:10px;margin-top:4px;line-height:1.35}.status{display:inline-flex;padding:5px 8px;border-radius:999px;font-size:10px;font-weight:800}.status.ok{background:var(--green-bg);color:var(--green)}.status.warning{background:var(--amber-bg);color:var(--amber)}.status.danger{background:var(--red-bg);color:var(--red)}.status.info{background:var(--blue-soft);color:#17619c}.status-sub{margin-top:5px}.actions-col{text-align:right}.actions{display:flex;justify-content:flex-end;align-items:center;gap:7px;white-space:nowrap}.action-link{padding:7px 9px;background:var(--blue-soft);color:#145d97;font-size:10px}.archive-form{margin:0}.archive-btn{padding:7px 9px;background:#fff0ee;color:var(--red);font-size:10px}.archive-btn:hover{background:#ffe0dc}.empty-state{text-align:center;padding:45px 20px;color:var(--muted)}.empty-icon{margin:0 auto 10px;display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:var(--blue-soft);color:var(--blue);font-size:10px;font-weight:900}.empty-state strong{display:block;color:var(--navy);font-size:14px}.empty-state p{font-size:11px;margin:5px 0 13px}.pagination-wrap{padding:15px 20px;border-top:1px solid #edf1f5;background:#fbfcfe}.pagination-wrap nav{display:flex;justify-content:center}.pagination-wrap svg{width:18px}.pagination-wrap a,.pagination-wrap span{font-size:11px}.pagination-wrap a{color:var(--blue)}@media(max-width:900px){.ops-hero,.section-heading{align-items:flex-start;flex-direction:column}.hero-actions{width:100%}.hero-actions a{flex:1}.section-heading .stat-card{width:100%;box-sizing:border-box}.form-grid{grid-template-columns:1fr}.scale{grid-template-columns:repeat(4,1fr)}}@media(max-width:620px){.school-ops{padding:2px 0 28px}.ops-hero{padding:22px 18px;border-radius:14px}.hero-copy h1{font-size:24px}.ops-nav{margin-bottom:17px}.panel.create-panel,.panel.action-panel,.records .panel-head{padding-left:15px;padding-right:15px}.panel-head{flex-direction:column}.form-footer,.search-form,.search-meta{align-items:stretch;flex-direction:column}.search-form .btn-primary,.search-form .btn-muted{width:100%}.search-input{width:100%;box-sizing:border-box}.scale{grid-template-columns:repeat(2,1fr)}.records .panel-head{padding-top:18px}.stat-card{width:100%}}
</style>
@endsection
