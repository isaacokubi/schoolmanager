{{--
    PRESENTATION NOTE: index view.
    This Blade template renders the user interface. Blade expressions display data
    prepared by controllers, while directives control layout, loops, conditions, and forms.
    These comments are not sent to the browser as visible page content.
--}}

{{-- Reuse the shared layout so this page inherits the site's common structure and styling. --}}
@extends('layouts.admin')
{{-- Begin a named layout section; the parent layout inserts this content into the matching yield. --}}
@section('title','Settings | School Manager')
{{-- Begin a named layout section; the parent layout inserts this content into the matching yield. --}}
@section('page_title','Settings')
{{-- Begin a named layout section; the parent layout inserts this content into the matching yield. --}}
@section('admin_content')
<div class="admin-page-head"><div><h1>School Settings</h1><p>Manage the school identity, branding and the officials used on generated CBC report cards.</p></div><a class="btn secondary" href="{{ route('admin.dashboard') }}">← Back to dashboard</a></div>
{{-- Render the following content only when this condition is true. --}}
@if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
{{-- Render the following content only when this condition is true. --}}
@if($errors->any())<div class="errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
{{-- Begin a form that sends the user's input to the route and HTTP method specified in its attributes. --}}
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="card form-grid">
{{-- Include Laravel's CSRF token so the form submission can be checked against cross-site request forgery. --}}
@csrf @method('PUT')
<div class="field"><label>School name</label><input name="school_name" value="{{ old('school_name',$settings['school_name']) }}" required></div>
<div class="field"><label>Phone</label><input name="school_phone" value="{{ old('school_phone',$settings['school_phone']) }}"></div>
<div class="field"><label>School &amp; password recovery email</label><input type="email" name="school_email" value="{{ old('school_email',$settings['school_email']) }}"><small>This address is used as the school contact email and becomes the authenticated administrator account email used by Forgot Password. It must be verified in Brevo if it is the sender address.</small></div>
<div class="field"><label>Currency</label><input name="currency" maxlength="3" value="{{ old('currency',$settings['currency']) }}" required></div>
<div class="field full"><label>Address</label><textarea name="school_address">{{ old('school_address',$settings['school_address']) }}</textarea></div>
<div class="field"><label>Academic year</label><input type="number" name="academic_year" min="2000" max="2100" value="{{ old('academic_year',$settings['academic_year']) }}"></div>
<div class="field"><label>Academic term</label><input name="academic_term" value="{{ old('academic_term',$settings['academic_term']) }}"></div>
<div class="field"><label>Timezone</label><input name="timezone" value="{{ old('timezone',$settings['timezone']) }}" required></div>

<div class="field full"><label>Head of Institution</label>
<select name="head_of_institution_user_id">
<option value="">Select the official Head of Institution</option>
{{-- Repeat this markup for each item in the supplied collection. --}}
@foreach($institutionHeads as $head)
<option value="{{ $head->id }}" {{ (string)old('head_of_institution_user_id',$settings['head_of_institution_user_id']) === (string)$head->id ? 'selected' : '' }}>{{ $head->name }} — {{ ucfirst($head->role) }} ({{ $head->email }}){{ $head->signature_path ? ' — signature uploaded' : ' — signature not uploaded' }}</option>
@endforeach
</select>
<small>The selected admin/manager is the person whose system name and uploaded signature will appear as Head of Institution.</small></div>

<div class="field"><label>School badge / logo</label><input type="file" name="school_badge" accept="image/jpeg,image/png,image/webp"><small>Official school badge used at the top of generated CBC report cards.</small>
{{-- Render the following content only when this condition is true. --}}
@if(!empty($settings['school_badge']))<div style="margin-top:10px"><img src="{{ route('media.file', ['path' => ltrim($settings['school_badge'], '/')]) }}" alt="School badge" style="max-width:90px;max-height:90px;object-fit:contain;border:1px solid #ddd;padding:5px;border-radius:8px"><label style="display:block;margin-top:8px"><input type="checkbox" name="remove_school_badge" value="1"> Remove current badge</label></div>@endif</div>

<div class="field"><label>Official school rubber stamp / seal</label><input type="file" name="school_stamp" accept="image/jpeg,image/png,image/webp"><small>Upload the official school stamp as an image. It will be printed automatically beside the Head of Institution signature.</small>
{{-- Render the following content only when this condition is true. --}}
@if(!empty($settings['school_stamp']))<div style="margin-top:10px"><img src="{{ route('media.file', ['path' => ltrim($settings['school_stamp'], '/')]) }}" alt="School stamp" style="max-width:130px;max-height:90px;object-fit:contain;border:1px solid #ddd;padding:5px;border-radius:8px"><label style="display:block;margin-top:8px"><input type="checkbox" name="remove_school_stamp" value="1"> Remove current stamp</label></div>@endif</div>

<div class="field full"><label>Mission</label><textarea name="mission">{{ old('mission',$settings['mission']) }}</textarea></div>
<div class="field full"><label>Vision</label><textarea name="vision">{{ old('vision',$settings['vision']) }}</textarea></div>
<div class="field full"><label>Values</label><textarea name="values">{{ old('values',$settings['values']) }}</textarea></div>
<div class="field full" style="display:flex;justify-content:flex-end"><button class="btn" type="submit">Save settings</button></div>
</form>
<div class="card" style="margin-top:18px;padding:18px"><h2 style="margin-top:0">Report-card signatures</h2><p>Class teachers and institution heads upload their own signatures from their authenticated staff portal. Parents do not upload a permanent staff signature; they sign individual report cards after reviewing them.</p><a class="btn secondary" href="{{ route('signature.index') }}">Manage my signature</a></div>
@endsection
