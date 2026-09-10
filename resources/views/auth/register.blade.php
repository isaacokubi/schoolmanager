@extends('layouts.public')
@section('title','Create Portal Account | School Manager')
@section('content')
<section class="page-hero">
    <div class="container">
        <p class="eyebrow">School portal</p>
        <h1>Create your account</h1>
        <p>Register as a pupil, parent, sponsor or teacher to access your personalised dashboard.</p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:820px">
        <div class="card">
            @if($errors->any())
                <div class="errors">
                    <ul style="margin:0;padding-left:20px">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="{{ route('register.submit') }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="form-grid">
                    <div class="field">
                        <label for="name">Full name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required maxlength="120">
                    </div>
                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="150">
                    </div>
                    <div class="field">
                        <label for="phone">Phone number</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" maxlength="30">
                    </div>
                    <div class="field">
                        <label for="portal_type">Account type</label>
                        <select name="portal_type" id="portal_type" required onchange="togglePortalFields()">
                            <option value="">Select account type</option>
                            <option value="pupil" {{ old('portal_type') === 'pupil' ? 'selected' : '' }}>Pupil</option>
                            <option value="parent" {{ old('portal_type') === 'parent' ? 'selected' : '' }}>Parent / Guardian</option>
                            <option value="sponsor" {{ old('portal_type') === 'sponsor' ? 'selected' : '' }}>Sponsor</option>
                            <option value="teacher" {{ old('portal_type') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                        </select>
                    </div>
                    <div class="field" id="admission-field">
                        <label for="admission_number">Admission number <span class="muted">(required for pupil, parent and sponsor)</span></label>
                        <input id="admission_number" name="admission_number" value="{{ old('admission_number') }}" maxlength="100">
                    </div>
                    <div class="field" id="employee-field">
                        <label for="employee_number">Employee number <span class="muted">(required for teachers)</span></label>
                        <input id="employee_number" name="employee_number" value="{{ old('employee_number') }}" maxlength="50" placeholder="e.g. T001">
                    </div>
                    <div class="field" id="relationship-field">
                        <label for="relationship">Relationship to pupil</label>
                        <input id="relationship" name="relationship" value="{{ old('relationship') }}" placeholder="e.g. Mother, Father, Guardian">
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" required minlength="8">
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8">
                    </div>
                </div>
                <div style="margin-top:24px">
                    <button class="btn" type="submit">Create portal account</button>
                </div>
            </form>

            <p style="margin:22px 0 0">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
        </div>
    </div>
</section>

<script>
function togglePortalFields(){
    const type = document.getElementById('portal_type').value;
    document.getElementById('admission-field').style.display = ['pupil','parent','sponsor'].includes(type) ? 'flex' : 'none';
    document.getElementById('employee-field').style.display = type === 'teacher' ? 'flex' : 'none';
    document.getElementById('relationship-field').style.display = ['parent','sponsor'].includes(type) ? 'flex' : 'none';
}
togglePortalFields();
</script>
@endsection
