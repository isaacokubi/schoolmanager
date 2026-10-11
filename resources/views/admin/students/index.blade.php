@extends('layouts.admin')
@section('title', 'Student Records | School Manager')
@section('page_title', 'Student records')
@section('admin_content')
{{-- Feature 1: a small student registry. Fee balances are changed only by confirmed payments. --}}
<div class="admin-page-head">
    <div>
        <h1>Student records</h1>
        <p>Add learners and keep their admission details and guardian phone numbers in one place.</p>
    </div>
    <div class="admin-actions">
        <a class="btn" href="{{ route('admin.students.create') }}">+ Add student</a>
        <a class="btn secondary" href="{{ route('admin.payments.index') }}">View M-Pesa payments</a>
    </div>
</div>

@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif

<div class="card">
    <form method="get" role="search">
        <label for="student-search">Search by name, admission number or guardian phone</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px">
            <input id="student-search" style="flex:1;min-width:220px" name="search" value="{{ request('search') }}" placeholder="e.g. Amina or ADM001">
            <button class="btn" type="submit">Search</button>
            @if(request('search'))<a class="btn secondary" href="{{ route('admin.students.index') }}">Clear</a>@endif
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Admission no.</th><th>Student name</th><th>Class</th><th>Guardian phone</th><th>Fee balance (KES)</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($students as $student)
                <tr>
                    <td>{{ $student->admission_number }}</td>
                    <td>{{ $student->name }}</td>
                    <td>{{ $student->linked_class_name ?: ($student->class_name ?: 'Not assigned') }}</td>
                    <td>{{ $student->parent_phone ?: '—' }}</td>
                    <td>{{ number_format((float)$student->fee_balance, 2) }}</td>
                    <td>
                        <a href="{{ route('admin.students.edit', $student->id) }}">Edit</a>
                        <form method="post" action="{{ route('admin.students.destroy', $student->id) }}" style="display:inline" onsubmit="return confirm('Archive this student record?')">
                            @csrf @method('DELETE')
                            <button type="submit" style="border:0;background:none;color:#b42318;cursor:pointer">Archive</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">No student records yet. Select “Add student” to create the first record.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px">{{ $students->links() }}</div>
</div>
@endsection
