@extends('layouts.admin')

@section('title','School Operations | School Manager')
@section('page_title','Operations')

@section('admin_content')
<div class="admin-page-head">
    <div>
        <h1>School Operations</h1>
        <p>Manage guardians, classes, staff, attendance, assessments and communications.</p>
    </div>
    <a class="btn secondary" href="{{ route('admin.dashboard') }}">← Back to dashboard</a>
</div>

@if(session('success'))
    <div class="alert success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert error">{{ $errors->first() }}</div>
@endif

<div class="ops-nav">
    @foreach(['parents'=>'Parents','classes'=>'Classes','teachers'=>'Teachers','subjects'=>'Subjects','attendance'=>'Attendance','exams'=>'Exams','results'=>'Results','announcements'=>'Announcements','events'=>'Events'] as $key => $label)
        <a href="{{ route('admin.operations',['section'=>$key]) }}" class="{{ $section === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

@php
    $configs = [
        'parents' => ['title'=>'Parents / Guardians','fields'=>[['name','Name','text',true],['phone','Phone','text',true],['email','Email','email',false],['relationship','Relationship','text',false]]],
        'classes' => ['title'=>'Classes','fields'=>[['name','Class name','text',true],['stream','Stream','text',false],['academic_year','Academic year','number',false]]],
        'teachers' => ['title'=>'Teachers','fields'=>[['name','Name','text',true],['email','Email','email',false],['phone','Phone','text',false],['employee_number','Employee number','text',false]]],
        'subjects' => ['title'=>'Subjects','fields'=>[['name','Subject name','text',true],['code','Code','text',false]]],
        'exams' => ['title'=>'Exams','fields'=>[['name','Exam name','text',true],['term','Term','text',true],['academic_year','Academic year','number',true],['start_date','Start date','date',false],['end_date','End date','date',false]]],
        'announcements' => ['title'=>'Announcements','fields'=>[['title','Title','text',true],['body','Message','textarea',true]]],
        'events' => ['title'=>'Events','fields'=>[['title','Title','text',true],['event_date','Date','date',true],['location','Location','text',false],['description','Description','textarea',false]]],
    ];
    $records = ${$section};
@endphp

@if(isset($configs[$section]))
    @php
        $cfg = $configs[$section];
    @endphp
    <div class="card" style="margin-bottom:16px">
        <div class="section-head"><div><h2>{{ $cfg['title'] }}</h2><p>Create a new record.</p></div></div>
        <form method="POST" action="{{ route('admin.operations.store') }}" class="grid-form">
            @csrf
            <input type="hidden" name="section" value="{{ $section }}">
            @foreach($cfg['fields'] as $field)
                <label>
                    {{ $field[1] }}
                    @if($field[2] === 'textarea')
                        <textarea name="{{ $field[0] }}" {{ $field[3] ? 'required' : '' }}>{{ old($field[0]) }}</textarea>
                    @else
                        <input type="{{ $field[2] }}" name="{{ $field[0] }}" value="{{ old($field[0]) }}" {{ $field[3] ? 'required' : '' }}>
                    @endif
                </label>
            @endforeach
            @if($section === 'subjects')
                <label>Teacher
                    <select name="teacher_id">
                        <option value="">Unassigned</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if($section === 'announcements')
                <label><input type="checkbox" name="published" value="1" checked> Publish now</label>
            @endif
            <button type="submit">Add Record</button>
        </form>
    </div>
@endif

@if($section === 'attendance')
    <div class="card" style="margin-bottom:16px">
        <div class="section-head"><div><h2>Record Attendance</h2><p>Capture daily attendance for a student.</p></div></div>
        <form method="POST" action="{{ route('admin.operations.attendance') }}" class="grid-form">
            @csrf
            <label>Student
                <select name="student_id" required>
                    <option value="">Select student</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} — {{ $student->admission_number }}</option>
                    @endforeach
                </select>
            </label>
            <label>Date<input type="date" name="attendance_date" value="{{ date('Y-m-d') }}" required></label>
            <label>Status
                <select name="status" required>
                    <option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option><option value="excused">Excused</option>
                </select>
            </label>
            <label>Notes<textarea name="notes"></textarea></label>
            <button type="submit">Save Attendance</button>
        </form>
    </div>
@endif

@if($section === 'results')
    <div class="card" style="margin-bottom:16px">
        <div class="section-head"><div><h2>Record Exam Result</h2><p>Enter marks and the system will calculate the grade.</p></div></div>
        <form method="POST" action="{{ route('admin.operations.results') }}" class="grid-form">
            @csrf
            <label>Exam
                <select name="exam_id" required>
                    <option value="">Select exam</option>
                    @foreach($exams as $exam)
                        <option value="{{ $exam->id }}">{{ $exam->name }} — {{ $exam->term }} {{ $exam->academic_year }}</option>
                    @endforeach
                </select>
            </label>
            <label>Student
                <select name="student_id" required>
                    <option value="">Select student</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} — {{ $student->admission_number }}</option>
                    @endforeach
                </select>
            </label>
            <label>Subject
                <select name="subject_id" required>
                    <option value="">Select subject</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Marks (0-100)<input type="number" name="marks" min="0" max="100" step="0.01" required></label>
            <label>Remarks<textarea name="remarks"></textarea></label>
            <button type="submit">Save Result</button>
        </form>
    </div>
@endif

<div class="card">
    <div class="section-head"><div><h2>{{ ucfirst($section) }}</h2><p>Search, edit and manage existing records.</p></div></div>
    <form method="GET" class="search">
        <input type="hidden" name="section" value="{{ $section }}">
        <input name="search" value="{{ request('search') }}" placeholder="Search {{ ucfirst($section) }}...">
        <button type="submit">Search</button>
        @if(request('search'))
            <a href="{{ route('admin.operations',['section'=>$section]) }}">Clear</a>
        @endif
    </form>

    <div class="table-wrap" style="margin-top:16px">
        <table class="table">
            <thead><tr><th>ID</th><th>Details</th><th>Actions</th></tr></thead>
            <tbody>
            @if($records->count() > 0)
                @foreach($records as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>
                            @if($section === 'attendance')
                                <strong>Student:</strong> {{ $row->student_name ?: '—' }} ({{ $row->admission_number ?: '—' }}) · <strong>Date:</strong> {{ $row->attendance_date }} · <strong>Status:</strong> {{ ucfirst($row->status) }}
                            @elseif($section === 'results')
                                <strong>Student:</strong> {{ $row->student_name ?: '—' }} ({{ $row->admission_number ?: '—' }}) · <strong>Exam:</strong> {{ $row->exam_name ?: '—' }} · <strong>Subject:</strong> {{ $row->subject_name ?: '—' }} · <strong>Marks:</strong> {{ $row->marks }} · <strong>Grade:</strong> {{ $row->grade ?: '—' }}
                            @else
                                @foreach((array)$row as $k => $v)
                                    @if(!in_array($k,['id','created_at','updated_at'],true))
                                        <strong>{{ ucwords(str_replace('_',' ',$k)) }}:</strong>
                                        @php
                                            $displayValue = (string) $v;
                                            if (strlen($displayValue) > 100) {
                                                $displayValue = substr($displayValue, 0, 100) . '...';
                                            }
                                        @endphp
                                        {{ $displayValue }} ·
                                    @endif
                                @endforeach
                            @endif
                        </td>
                        <td>
                            <details>
                                <summary>Edit</summary>
                                <form method="POST" action="{{ route('admin.operations.update',$row->id) }}" class="grid-form">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="section" value="{{ $section }}">

                                    @if($section === 'attendance')
                                        <label>Student
                                            <select name="student_id" required>
                                                @foreach($students as $student)
                                                    <option value="{{ $student->id }}" {{ $row->student_id == $student->id ? 'selected' : '' }}>{{ $student->name }} — {{ $student->admission_number }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label>Date<input type="date" name="attendance_date" value="{{ $row->attendance_date }}" required></label>
                                        <label>Status
                                            <select name="status" required>
                                                @foreach(['present','absent','late','excused'] as $status)
                                                    <option value="{{ $status }}" {{ $row->status === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label>Notes<textarea name="notes">{{ $row->notes }}</textarea></label>
                                    @elseif($section === 'results')
                                        <label>Exam
                                            <select name="exam_id" required>
                                                @foreach($exams as $exam)
                                                    <option value="{{ $exam->id }}" {{ $row->exam_id == $exam->id ? 'selected' : '' }}>{{ $exam->name }} — {{ $exam->term }} {{ $exam->academic_year }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label>Student
                                            <select name="student_id" required>
                                                @foreach($students as $student)
                                                    <option value="{{ $student->id }}" {{ $row->student_id == $student->id ? 'selected' : '' }}>{{ $student->name }} — {{ $student->admission_number }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label>Subject
                                            <select name="subject_id" required>
                                                @foreach($subjects as $subject)
                                                    <option value="{{ $subject->id }}" {{ $row->subject_id == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label>Marks<input type="number" name="marks" min="0" max="100" step="0.01" value="{{ $row->marks }}" required></label>
                                        <label>Remarks<textarea name="remarks">{{ $row->remarks }}</textarea></label>
                                    @else
                                        @foreach((array)$row as $k => $v)
                                            @if(!in_array($k,['id','created_at','updated_at','published_at','grade','teacher_id'],true))
                                                @if($k === 'published')
                                                    <label><input type="checkbox" name="published" value="1" {{ $v ? 'checked' : '' }}> Published</label>
                                                @elseif(in_array($k,['body','description','notes'],true))
                                                    <label>{{ ucwords(str_replace('_',' ',$k)) }}
                                                        <textarea name="{{ $k }}">{{ $v }}</textarea>
                                                    </label>
                                                @else
                                                    @php
                                                        $inputType = 'text';
                                                        if ($k === 'email') {
                                                            $inputType = 'email';
                                                        } elseif ($k === 'academic_year') {
                                                            $inputType = 'number';
                                                        } elseif (in_array($k,['start_date','end_date','event_date'],true)) {
                                                            $inputType = 'date';
                                                        }
                                                    @endphp
                                                    <label>{{ ucwords(str_replace('_',' ',$k)) }}
                                                        <input type="{{ $inputType }}" name="{{ $k }}" value="{{ $v }}">
                                                    </label>
                                                @endif
                                            @endif
                                        @endforeach
                                        @if($section === 'subjects')
                                            <label>Teacher
                                                <select name="teacher_id">
                                                    <option value="">Unassigned</option>
                                                    @foreach($teachers as $teacher)
                                                        <option value="{{ $teacher->id }}" {{ ($row->teacher_id ?? null) == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                        @endif
                                    @endif
                                    <button type="submit">Update</button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route('admin.operations.destroy',$row->id) }}" onsubmit="return confirm('Delete this record? This cannot be undone.');" style="margin-top:6px">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="section" value="{{ $section }}">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="3"><div class="empty-state"><strong>No records found</strong><span>Create a record using the form above.</span></div></td></tr>
            @endif
            </tbody>
        </table>
    </div>
    {{ $records->links() }}
</div>
@endsection
