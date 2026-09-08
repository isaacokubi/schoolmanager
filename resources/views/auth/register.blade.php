@extends('layouts.app')
@section('title','Create Portal Account | School Manager')
@section('body')
<div style="min-height:100vh;padding:45px 20px;background:linear-gradient(135deg,#edf4ff,#f7f9fc)"><div class="card" style="width:min(680px,100%);margin:auto">
<p class="eyebrow">School portal</p><h1>Create your account</h1><p class="muted">Register as a pupil, parent, sponsor or teacher to access your personalised dashboard.</p>
@if($errors->any())<div class="errors"><ul style="margin:0;padding-left:20px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ route('register.submit') }}"><input type="hidden" name="_token" value="{{ csrf_token() }}">
<div class="form-grid">
<div class="field"><label>Full name</label><input name="name" value="{{ old('name') }}" required maxlength="120"></div>
<div class="field"><label>Email address</label><input type="email" name="email" value="{{ old('email') }}" required maxlength="150"></div>
<div class="field"><label>Phone number</label><input name="phone" value="{{ old('phone') }}" maxlength="30"></div>
<div class="field"><label>Account type</label><select name="portal_type" id="portal_type" required onchange="togglePortalFields()"><option value="">Select account type</option><option value="pupil" {{ old('portal_type') === 'pupil' ? 'selected' : '' }}>Pupil</option><option value="parent" {{ old('portal_type') === 'parent' ? 'selected' : '' }}>Parent / Guardian</option><option value="sponsor" {{ old('portal_type') === 'sponsor' ? 'selected' : '' }}>Sponsor</option><option value="teacher" {{ old('portal_type') === 'teacher' ? 'selected' : '' }}>Teacher</option></select></div>
<div class="field" id="admission-field"><label>Admission number <span class="muted">(required for pupil, parent and sponsor)</span></label><input name="admission_number" value="{{ old('admission_number') }}" maxlength="100"></div>
<div class="field" id="employee-field"><label>Employee number <span class="muted">(required for teachers)</span></label><input name="employee_number" value="{{ old('employee_number') }}" maxlength="50" placeholder="e.g. T001"></div>
<div class="field" id="relationship-field"><label>Relationship to pupil</label><input name="relationship" value="{{ old('relationship') }}" placeholder="e.g. Mother, Father, Guardian"></div>
<div class="field"><label>Password</label><input type="password" name="password" required minlength="8"></div>
<div class="field"><label>Confirm password</label><input type="password" name="password_confirmation" required minlength="8"></div>
</div><br><button class="btn" type="submit" style="width:100%">Create portal account</button></form>
<p style="margin-bottom:0">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
</div></div>
<script>function togglePortalFields(){const t=document.getElementById('portal_type').value;document.getElementById('admission-field').style.display=['pupil','parent','sponsor'].includes(t)?'flex':'none';document.getElementById('employee-field').style.display=t==='teacher'?'flex':'none';document.getElementById('relationship-field').style.display=['parent','sponsor'].includes(t)?'flex':'none';}togglePortalFields();</script>
@endsection
