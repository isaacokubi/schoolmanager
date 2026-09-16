@extends('layouts.admin')
@section('title','Students | School Manager')
@section('page_title','Students')
@section('admin_content')
@php
    $total = $students->total();
    $outstanding = $students->getCollection()->sum(fn ($student) => max((float) $student->fee_balance, 0));
@endphp

<div class="admin-page-head student-page-head">
    <div>
        <div class="eyebrow">STUDENT REGISTRY</div>
        <h1>Student Management</h1>
        <p>Manage enrolment, class placement, guardians and fee accounts securely.</p>
    </div>
    <div class="admin-actions">
        <a class="btn secondary" href="{{ route('admin.operations',['section'=>'parents']) }}">Parents & guardians</a>
        <a class="btn secondary" href="{{ route('admin.operations',['section'=>'classes']) }}">Classes & streams</a>
        <a class="btn" href="{{ route('admin.students.create') }}">+ Add student</a>
    </div>
</div>

@if(session('success'))
    <div class="alert success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert error" role="alert">{{ $errors->first() }}</div>
@endif

<div class="student-stats">
    <div class="student-stat"><span>Total records</span><strong>{{ number_format($total) }}</strong><small>Records matching the current filters</small></div>
    <div class="student-stat"><span>On this page</span><strong>{{ number_format($students->count()) }}</strong><small>Visible student records</small></div>
    <div class="student-stat"><span>Outstanding on page</span><strong>KES {{ number_format($outstanding, 2) }}</strong><small>Positive balances among visible records</small></div>
</div>

<div class="card student-filter-card">
    <form method="get" class="student-filters" role="search">
        <div class="filter-search">
            <label for="student-search">Search students</label>
            <input id="student-search" name="search" value="{{ request('search') }}" autocomplete="off" placeholder="Name, admission no., class, guardian or phone">
        </div>
        <div>
            <label for="class-filter">Class / stream</label>
            <select id="class-filter" name="class_id">
                <option value="">All classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ (string)request('class_id') === (string)$class->id ? 'selected' : '' }}>
                        {{ $class->name }}{{ $class->stream ? ' — '.$class->stream : '' }}{{ $class->academic_year ? ' ('.$class->academic_year.')' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="balance-filter">Fee status</label>
            <select id="balance-filter" name="balance">
                <option value="">All balances</option>
                <option value="clear" {{ request('balance') === 'clear' ? 'selected' : '' }}>Paid / clear</option>
                <option value="outstanding" {{ request('balance') === 'outstanding' ? 'selected' : '' }}>Outstanding</option>
            </select>
        </div>
        <div class="filter-actions">
            <button class="btn" type="submit">Apply filters</button>
            @if(request()->hasAny(['search','class_id','balance']))
                <a class="btn secondary" href="{{ route('admin.students.index') }}">Reset</a>
            @endif
        </div>
    </form>
</div>

<div class="card student-table-card">
    <div class="section-head">
        <div>
            <h2>Students</h2>
            <p>Financial balances are read-only here and should be changed through recorded fee transactions.</p>
        </div>
        <span class="record-count">{{ number_format($total) }} {{ $total === 1 ? 'record' : 'records' }}</span>
    </div>

    <div class="table-wrap student-table-wrap">
        <table class="table student-table" aria-label="Student registry">
            <thead>
                <tr>
                    <th class="student-col">Student</th>
                    <th class="admission-col">Admission no.</th>
                    <th class="class-col">Class / stream</th>
                    <th class="guardian-col">Parent / guardian</th>
                    <th class="phone-col">Phone</th>
                    <th class="balance-col">Fee balance</th>
                    <th class="status-col">Status</th>
                    <th class="actions-col">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($students as $student)
                @php $balance = (float) $student->fee_balance; @endphp
                <tr>
                    <td data-label="Student"><strong>{{ $student->name }}</strong></td>
                    <td data-label="Admission no."><span class="mono">{{ $student->admission_number }}</span></td>
                    <td data-label="Class / stream">{{ $student->linked_class_name ?: ($student->class_name ?: 'Unassigned') }}{{ $student->linked_class_stream ? ' — '.$student->linked_class_stream : '' }}</td>
                    <td data-label="Parent / guardian">{{ $student->linked_parent_name ?: ($student->parent_name ?: 'Not linked') }}</td>
                    <td data-label="Phone">{{ $student->parent_phone ? preg_replace('/^(?:\+254|0)(\d{3})(\d{3})(\d{3})$/', '+254 $1 $2 $3', $student->parent_phone) : '—' }}</td>
                    <td data-label="Fee balance"><strong class="{{ $balance > 0 ? 'balance-due' : 'balance-clear' }}">KES {{ number_format($balance, 2) }}</strong></td>
                    <td data-label="Status"><span class="status-pill {{ $balance > 0 ? 'warning' : 'success' }}">{{ $balance > 0 ? 'Outstanding' : 'Clear' }}</span></td>
                    <td data-label="Actions" class="actions-cell">
                        <a class="table-action" href="{{ route('admin.students.edit',$student->id) }}">Edit</a>
                        <form method="post" action="{{ route('admin.students.destroy',$student->id) }}" class="delete-form" onsubmit="return confirm('Delete this student record? Use this only when the record has no linked financial, attendance, examination or admission history.')">
                            @csrf @method('DELETE')
                            <button class="table-action danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state"><strong>No students found</strong><span>Try a different search or reset the filters. You can also add a new student.</span><a class="btn secondary" href="{{ route('admin.students.create') }}">Add student</a></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($students->hasPages())
        <nav class="student-pagination" aria-label="Student registry pagination">
            <div class="pagination-summary">Showing <strong>{{ $students->firstItem() ?? 0 }}</strong>–<strong>{{ $students->lastItem() ?? 0 }}</strong> of <strong>{{ number_format($students->total()) }}</strong> students</div>
            <div class="pagination-controls">
                @if($students->onFirstPage())
                    <span class="page-link disabled" aria-disabled="true">Previous</span>
                @else
                    <a class="page-link" href="{{ $students->previousPageUrl() }}" rel="prev">Previous</a>
                @endif
                @foreach($students->getUrlRange(max(1, $students->currentPage() - 2), min($students->lastPage(), $students->currentPage() + 2)) as $page => $url)
                    @if($page == $students->currentPage())
                        <span class="page-link current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
                @if($students->hasMorePages())
                    <a class="page-link" href="{{ $students->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="page-link disabled" aria-disabled="true">Next</span>
                @endif
            </div>
        </nav>
    @endif
</div>

<style>
.eyebrow{font-size:11px;letter-spacing:.14em;font-weight:900;color:#1769df;margin-bottom:7px}.student-page-head{align-items:center}.student-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:18px}.student-stat{background:linear-gradient(145deg,#fff,#f8fbff);border:1px solid #e2e9f3;border-radius:15px;padding:16px 18px;box-shadow:0 7px 22px rgba(24,45,75,.045);min-width:0}.student-stat span{display:block;color:#697a90;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.student-stat strong{display:block;color:#15243a;font-size:23px;line-height:1.3;margin:4px 0;overflow-wrap:anywhere}.student-stat small{color:#8a98aa;font-size:12px;line-height:1.45}.student-filter-card{margin-bottom:18px}.student-filters{display:grid;grid-template-columns:minmax(250px,1.7fr) minmax(190px,1fr) minmax(170px,.8fr) auto;gap:12px;align-items:end}.student-filters label{display:block;font-size:12px;font-weight:850;color:#52637a;margin-bottom:6px}.student-filters input,.student-filters select{width:100%;box-sizing:border-box;padding:12px 12px;border:1px solid #d5dfeb;border-radius:10px;background:#fff;color:#26384f;font:inherit;font-size:14px;line-height:1.4;outline:none}.student-filters input:focus,.student-filters select:focus{border-color:#5590e8;box-shadow:0 0 0 3px rgba(23,105,223,.1)}.filter-actions{display:flex;gap:8px}.student-filter-card .btn{font-size:13px;padding:11px 15px}.student-table-card{overflow:hidden}.student-table-card .section-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px}.student-table-card .section-head h2{font-size:20px}.student-table-card .section-head p{max-width:680px;font-size:13px}.record-count{white-space:nowrap;background:#f2f6fb;color:#607189;border:1px solid #e1e8f1;border-radius:999px;padding:7px 11px;font-size:12px;font-weight:800}.student-table-wrap{border:0;border-radius:0;background:#fff}.student-table{width:100%;min-width:1120px;table-layout:fixed;font-size:13.5px;line-height:1.45}.student-table th{white-space:nowrap;font-size:11.5px;line-height:1.25;padding:12px 14px;color:#52637a;background:#f6f8fb}.student-table td{vertical-align:middle;padding:13px 14px;font-size:13.5px;color:#34475f;overflow-wrap:anywhere}.student-table td strong{font-size:13.5px}.student-table tbody tr:hover{background:#fbfdff}.student-col{width:16%}.admission-col{width:15%}.class-col{width:14%}.guardian-col{width:15%}.phone-col{width:13%}.balance-col{width:12%}.status-col{width:8%}.actions-col{width:11%}.mono{font-variant-numeric:tabular-nums;letter-spacing:.01em;color:#44566f;overflow-wrap:anywhere}.balance-due{color:#a65b00;white-space:nowrap}.balance-clear{color:#16724b;white-space:nowrap}.status-pill{display:inline-flex;align-items:center;border-radius:999px;padding:6px 9px;font-size:10.5px;font-weight:900;white-space:nowrap}.status-pill.success{background:#eaf8f1;color:#157347}.status-pill.warning{background:#fff5df;color:#9a5a00}.actions-cell{white-space:nowrap}.delete-form{display:inline;margin:0}.table-action{display:inline-flex;align-items:center;border:0;background:transparent;color:#1769df;text-decoration:none;font:inherit;font-size:12.5px;font-weight:850;cursor:pointer;padding:5px 4px}.table-action:hover{text-decoration:underline}.table-action.danger{color:#b42318}.student-pagination{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:18px 0 2px;border-top:1px solid #edf1f6;margin-top:2px}.pagination-summary{font-size:13px;color:#748399}.pagination-controls{display:flex;align-items:center;gap:5px;flex-wrap:wrap;justify-content:flex-end}.page-link{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 11px;box-sizing:border-box;border:1px solid #dce4ee;border-radius:9px;background:#fff;color:#42566f;text-decoration:none;font-size:12.5px;font-weight:800}.page-link:hover{border-color:#9bbbe7;color:#1769df;background:#f7faff}.page-link.current{border-color:#1769df;background:#1769df;color:#fff}.page-link.disabled{color:#a6b1bf;background:#f7f9fb;border-color:#e7ecf2;cursor:not-allowed}.empty-state{display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px;padding:48px 20px;color:#7a899d;text-align:center}.empty-state strong{color:#25364e;font-size:15px}.empty-state .btn{margin-top:7px}
@media(max-width:1180px){.student-filters{grid-template-columns:1fr 1fr}.filter-search{grid-column:1/-1}.filter-actions{grid-column:1/-1}.student-stats{grid-template-columns:1fr 1fr}.student-stat:last-child{grid-column:1/-1}}
@media(max-width:800px){.student-page-head{display:block}.student-stats{grid-template-columns:1fr}.student-stat:last-child{grid-column:auto}.student-filters{grid-template-columns:1fr}.filter-search,.filter-actions{grid-column:auto}.filter-actions{justify-content:flex-start}.student-table{min-width:1120px}.student-table-card{overflow-x:auto}.student-table-card .section-head{min-width:760px}.student-pagination{min-width:760px;align-items:flex-start;flex-direction:column}.pagination-controls{justify-content:flex-start}}
@media(max-width:560px){.admin-actions .btn{width:100%;text-align:center;box-sizing:border-box}.student-stat strong{font-size:20px}.student-pagination{gap:12px}.pagination-controls{width:100%}.page-link{min-width:34px}}
</style>
@endsection
