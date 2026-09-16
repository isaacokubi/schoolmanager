@extends('layouts.admin')
@section('title', $student ? 'Edit Student | School Manager' : 'Add Student | School Manager')
@section('page_title', $student ? 'Edit Student' : 'Add Student')
@section('admin_content')
<div class="admin-page-head">
    <div>
        <div class="eyebrow">STUDENT REGISTRY</div>
        <h1>{{ $student ? 'Edit Student' : 'Add Student' }}</h1>
        <p>{{ $student ? 'Update enrolment and guardian details without changing the financial ledger.' : 'Create a student record with a unique admission number and optional class and guardian links.' }}</p>
    </div>
    <div class="admin-actions"><a class="btn secondary" href="{{ route('admin.students.index') }}">← Back to students</a></div>
</div>

@if($errors->any())
    <div class="alert error" role="alert">
        <strong>Please correct the following:</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="card student-form-card">
    <form method="post" action="{{ $student ? route('admin.students.update',$student->id) : route('admin.students.store') }}" novalidate>
        @csrf
        @if($student)<input type="hidden" name="_method" value="PUT">@endif

        <div class="form-section">
            <div><h2>Student details</h2><p>Use the student's official school admission information.</p></div>
            <div class="form-grid">
                <label>Admission number <span class="required">*</span>
                    <input name="admission_number" required maxlength="50" value="{{ old('admission_number',$student->admission_number ?? '') }}" autocomplete="off">
                    <small>Must be unique within the school.</small>
                </label>
                <label>Student full name <span class="required">*</span>
                    <input name="name" required maxlength="150" value="{{ old('name',$student->name ?? '') }}" autocomplete="name">
                </label>
            </div>
        </div>

        <div class="form-section">
            <div><h2>Class placement</h2><p>Link the student to the current academic class and stream.</p></div>
            <div class="form-grid">
                <label>Class / stream
                    <select name="class_id">
                        <option value="">Unassigned</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (string)old('class_id',$student->class_id ?? '')===(string)$class->id?'selected':'' }}>
                                {{ $class->name }}{{ $class->stream ? ' — '.$class->stream : '' }}{{ $class->academic_year ? ' ('.$class->academic_year.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <small>The linked class is the source of truth; legacy class text is maintained automatically.</small>
                </label>
            </div>
        </div>

        <div class="form-section">
            <div><h2>Parent / guardian</h2><p>Link an existing guardian where possible. Keep contact details current for school communication.</p></div>
            <div class="form-grid">
                <label>Parent / guardian
                    <select name="parent_id">
                        <option value="">Unassigned</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" {{ (string)old('parent_id',$student->parent_id ?? '')===(string)$parent->id?'selected':'' }}>{{ $parent->name }}{{ $parent->phone ? ' — '.$parent->phone : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Guardian phone
                    <input name="parent_phone" maxlength="13" inputmode="tel" placeholder="07XX XXX XXX or +254 7XX XXX XXX" value="{{ old('parent_phone',$student->parent_phone ?? '') }}" autocomplete="tel">
                    <small>Kenyan mobile numbers are stored in international +254 format.</small>
                </label>
            </div>
        </div>

        <div class="form-section legacy-section">
            <div><h2>Legacy compatibility</h2><p>These fields support older records. Prefer the linked class and guardian above for new data.</p></div>
            <div class="form-grid">
                <label>Legacy class name
                    <input name="class_name" maxlength="100" value="{{ old('class_name',$student->class_name ?? '') }}">
                </label>
                <label>Legacy parent / guardian name
                    <input name="parent_name" maxlength="150" value="{{ old('parent_name',$student->parent_name ?? '') }}">
                </label>
            </div>
        </div>

        <div class="ledger-note">
            <strong>Fee balance</strong>
            <span>{{ $student ? 'Current balance: KES '.number_format((float)($student->fee_balance ?? 0), 2) : 'New students start with a KES 0.00 balance.' }}</span>
            <small>Balances are controlled by recorded fee transactions and cannot be edited from this form.</small>
        </div>

        <div class="form-actions">
            <a class="btn secondary" href="{{ route('admin.students.index') }}">Cancel</a>
            <button class="btn" type="submit">{{ $student ? 'Save changes' : 'Create student' }}</button>
        </div>
    </form>
</div>

<style>
.eyebrow{font-size:10px;letter-spacing:.14em;font-weight:900;color:#1769df;margin-bottom:7px}.student-form-card{max-width:980px}.form-section{padding:4px 0 22px;margin-bottom:20px;border-bottom:1px solid #edf1f6}.form-section h2{margin:0 0 4px;font-size:16px;color:#20324b}.form-section p{margin:0 0 14px;color:#7a899d;font-size:12px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.form-grid label{display:block;color:#4f6077;font-size:12px;font-weight:850}.form-grid input,.form-grid select{box-sizing:border-box;width:100%;margin-top:6px;padding:11px 12px;border:1px solid #d5dfeb;border-radius:10px;background:#fff;color:#26384f;font:inherit;outline:none}.form-grid input:focus,.form-grid select:focus{border-color:#5590e8;box-shadow:0 0 0 3px rgba(23,105,223,.1)}.form-grid small{display:block;margin-top:5px;color:#8a98aa;font-size:10px;font-weight:500}.required{color:#b42318}.legacy-section{background:#fafbfd;border:1px solid #e7ecf3;border-radius:12px;padding:16px}.ledger-note{display:grid;grid-template-columns:auto 1fr;gap:3px 14px;background:#f2f7ff;border:1px solid #dbe9fb;border-radius:12px;padding:14px 16px;margin-bottom:20px}.ledger-note strong{color:#284362}.ledger-note span{color:#1769df;font-weight:850}.ledger-note small{grid-column:1/-1;color:#708199;font-size:11px}.form-actions{display:flex;justify-content:flex-end;gap:9px}.alert ul{margin:7px 0 0;padding-left:20px}@media(max-width:700px){.form-grid{grid-template-columns:1fr}.ledger-note{grid-template-columns:1fr}.ledger-note small{grid-column:auto}.form-actions{justify-content:stretch}.form-actions .btn{flex:1;text-align:center}}
</style>
@endsection
