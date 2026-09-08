@extends('layouts.app')
@section('title', $student ? 'Edit Student' : 'Add Student')
@section('body')
<div class="admin-shell"><header class="admin-top"><strong>{{ $student ? 'Edit Student' : 'Add Student' }}</strong><a style="color:#fff" href="{{ route('admin.students.index') }}">Students</a></header><main class="admin-main">
@if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
<div class="card"><form method="post" action="{{ $student ? route('admin.students.update',$student->id) : route('admin.students.store') }}">@csrf @if($student)<input type="hidden" name="_method" value="PUT">@endif
<div class="grid">
<label>Admission Number<input name="admission_number" required maxlength="50" value="{{ old('admission_number',$student->admission_number ?? '') }}">@error('admission_number')<small>{{ $message }}</small>@enderror</label>
<label>Student Name<input name="name" required maxlength="150" value="{{ old('name',$student->name ?? '') }}">@error('name')<small>{{ $message }}</small>@enderror</label>
<label>Class / Stream<select name="class_id"><option value="">Unassigned</option>@foreach($classes as $class)<option value="{{ $class->id }}" {{ (string)old('class_id',$student->class_id ?? '')===(string)$class->id?'selected':'' }}>{{ $class->name }}{{ $class->stream ? ' — '.$class->stream : '' }}{{ $class->academic_year ? ' ('.$class->academic_year.')' : '' }}</option>@endforeach</select></label>
<label>Parent / Guardian<select name="parent_id"><option value="">Unassigned</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" {{ (string)old('parent_id',$student->parent_id ?? '')===(string)$parent->id?'selected':'' }}>{{ $parent->name }} — {{ $parent->phone }}</option>@endforeach</select></label>
<label>Legacy Class Name<input name="class_name" maxlength="100" value="{{ old('class_name',$student->class_name ?? '') }}"><small>Used for compatibility when no class is linked.</small></label>
<label>Parent/Guardian Name<input name="parent_name" maxlength="150" value="{{ old('parent_name',$student->parent_name ?? '') }}"><small>Legacy field; linked parent is preferred.</small></label>
<label>Parent Phone<input name="parent_phone" maxlength="13" placeholder="+2547XXXXXXXX" value="{{ old('parent_phone',$student->parent_phone ?? '') }}">@error('parent_phone')<small>{{ $message }}</small>@enderror</label>
<label>Fee Balance<input type="number" min="0" step="0.01" name="fee_balance" value="{{ old('fee_balance',$student->fee_balance ?? 0) }}">@error('fee_balance')<small>{{ $message }}</small>@enderror</label>
</div><br><button class="btn" type="submit">{{ $student ? 'Update Student' : 'Save Student' }}</button></form></div></main></div>
@endsection
