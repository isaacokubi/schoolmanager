@extends('pages.layout')
@section('title','Admissions')
@section('content')<h1>Admissions</h1><p>Admission requirements and online application/enquiry functionality will be available here.</p><form method="post" action="#"><input type="hidden" name="_token" value="{{ csrf_token() }}"><p><label>Parent/Guardian name<br><input name="parent_name" required></label></p><p><label>Student name<br><input name="student_name" required></label></p><p><label>Phone number<br><input name="phone" required></label></p><button type="submit">Submit enquiry</button></form>@endsection
